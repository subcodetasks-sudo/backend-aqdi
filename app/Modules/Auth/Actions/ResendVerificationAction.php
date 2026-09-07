<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class ResendVerificationAction
{
    public function __construct(private readonly SendUserAuthSmsAction $sms) {}

    /**
     * @return array{ok: true}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        if (! $user) {
            return ['ok' => false, 'message' => trans('api.user_not_found'), 'code' => 404];
        }

        if ($user->isVerified()) {
            return ['ok' => false, 'message' => trans('api.verified_account'), 'code' => 409];
        }

        $otpType = 'resend_account_verification';

        $recentOtp = $this->sms->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

            return ['ok' => false, 'message' => 'يرجى الانتظار قبل طلب رمز تحقق جديد.', 'code' => 429];
        }

        $user->verification_code = rand(1000, 9999);
        $user->save();

        $recipients = $user->mobile;
        $body = 'كود تأكيد حسابك الخاص في عقدي هو: '.$user->verification_code;
        $sender = 'AqdiCo';
        $smsId = '25489';

        $smsResult = $this->sms->execute($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {
            return ['ok' => true];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.error_sending_sms')];
    }
}
