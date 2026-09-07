<?php

namespace App\Modules\Auth\Actions;

use App\Models\SmsLog;
use App\Modules\Auth\Support\AuthMobile;
use App\Services\TaqnyatSmsService;

class SendUserAuthSmsAction
{
    public function execute($body, $recipients, $sender, $smsId, $type = null)
    {
        return app(TaqnyatSmsService::class)->sendAndLog(
            $body,
            $recipients,
            $type,
            auth()->id(),
            $sender,
            $smsId,
            AuthMobile::normalizeSaudiMobile((string) $recipients)
        );
    }

    public function recentSmsLog(string $type, string $mobile, int $minutes = 2): ?SmsLog
    {
        return SmsLog::query()
            ->whereIn('phone_number', AuthMobile::lookupVariants($mobile))
            ->where('type', $type)
            ->where('sent_at', '>=', now()->subMinutes($minutes))
            ->first();
    }

    public function logBlockedResend(?int $userId, string $formattedMobile, string $otpType): void
    {
        SmsLog::create([
            'user_id' => $userId,
            'phone_number' => $formattedMobile,
            'message' => 'Resend blocked: cooldown not expired',
            'type' => $otpType,
            'sms_id' => null,
            'sent_at' => now(),
        ]);
    }
}
