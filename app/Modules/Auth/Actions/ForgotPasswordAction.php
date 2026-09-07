<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class ForgotPasswordAction
{
    public function __construct(private readonly SendUserAuthSmsAction $sms) {}

    /**
     * @return array{ok: true}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->firstOrFail();
        $recipients = $user->mobile;

        $otpType = 'forgot_password';

        $recentOtp = $this->sms->recentSmsLog($otpType, $formattedMobile);

        if ($recentOtp) {
            $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

            return ['ok' => false, 'message' => 'يرجى الانتظار قبل طلب رمز جديد.', 'code' => 429];
        }

        $user->reset_password_code = User::generateResetPasswordCode();
        $user->save();

        $body = 'الكود الخاص بتغير كلمة مرور حسابك في عقدي هو : '.$user->reset_password_code;
        $sender = 'AqdiCo';
        $smsId = '25489';

        $smsResult = $this->sms->execute($body, $recipients, $sender, $smsId, $otpType);

        if ($smsResult === true) {
            return ['ok' => true];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.send_reset_password_code_failed')];
    }
}
