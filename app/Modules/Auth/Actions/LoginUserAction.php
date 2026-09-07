<?php

namespace App\Modules\Auth\Actions;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function __construct(
        private readonly SendUserAuthSmsAction $sms,
        private readonly AppendLoginCouponNotificationAction $loginCoupon,
    ) {}

    /**
     * @return array{ok: true, result: array<string, mixed>}|array{ok: false, message: string, code?: int}|array{ok: true, unverified: true, result: array<string, mixed>}
     */
    public function execute(Request $request): array
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        if (! $user) {
            return ['ok' => false, 'message' => trans('api.credentials_error')];
        }

        if (! $user->isVerified()) {
            $otpType = 'login_account_verification';

            $recentOtp = $this->sms->recentSmsLog($otpType, $formattedMobile);

            if ($recentOtp) {
                $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

                return ['ok' => false, 'message' => 'يرجى الانتظار قبل طلب رمز تحقق جديد.', 'code' => 429];
            }

            $verificationCode = rand(1000, 9999);
            $user->verification_code = $verificationCode;
            $user->save();

            $recipients = $user->mobile;
            $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$verificationCode;
            $sender = 'AqdiCo';
            $smsId = '25489';

            $this->sms->execute($body, $recipients, $sender, $smsId, $otpType);

            return [
                'ok' => true,
                'unverified' => true,
                'result' => [
                    'user' => new UserResource($user),
                ],
            ];
        }

        if (! $user->isActive()) {
            return ['ok' => false, 'message' => trans('api.block_account')];
        }

        if (Hash::check($request->password, $user->password)) {
            if ($request->has('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }

            $user->refresh();
            $result = [
                'user' => new UserResource($user),
                'token' => $user->createToken('user_token')->plainTextToken,
            ];
            $this->loginCoupon->execute($result, $user);

            return ['ok' => true, 'result' => $result];
        }

        return ['ok' => false, 'message' => trans('api.credentials_error')];
    }
}
