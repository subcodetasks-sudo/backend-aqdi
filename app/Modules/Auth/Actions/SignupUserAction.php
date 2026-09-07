<?php

namespace App\Modules\Auth\Actions;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class SignupUserAction
{
    public function __construct(private readonly SendUserAuthSmsAction $sms) {}

    /**
     * @return array{ok: true, user: UserResource}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $otpType = 'signup';
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $recentOtp = $this->sms->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            $this->sms->logBlockedResend(null, $formattedMobile, $otpType);

            return ['ok' => false, 'message' => 'يرجى الانتظار قبل طلب رمز تحقق جديد.', 'code' => 429];
        }

        $data = $request->only(['fname', 'mobile']);
        $data['mobile'] = $formattedMobile;
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        $data['password'] = bcrypt($request->password);

        $verificationCode = rand(1000, 9999);
        $data['verification_code'] = $verificationCode;

        if ($request->filled('platform')) {
            $data['platform'] = User::normalizePlatform((string) $request->input('platform'));
        }

        $user = User::create($data);

        $recipients = $user->mobile;
        $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$verificationCode;
        $sender = 'AqdiCo';
        $smsId = '25489';

        $smsResult = $this->sms->execute($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {
            return ['ok' => true, 'user' => new UserResource($user)];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.error_sending_sms')];
    }
}
