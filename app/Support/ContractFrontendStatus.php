<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ContractStatus;

/**
 * Unified frontend status payload for API v2 + Firebase.
 * Keys: status (machine), status_label (Arabic).
 */
class ContractFrontendStatus
{
    /** @var array<string, string> */
    private const NAME_TO_KEY = [
        'جديد' => 'new',
        'قيد المراجعة' => 'under_review',
        'مكتمل' => 'completed',
        'ملغى' => 'cancelled',
        'معلق' => 'on_hold',
        'مستلم' => 'received',
        'مستلم من الموظف' => 'received_by_employee',
        'تم الدفع' => 'paid',
        'إرسال مسودة العقد لكم عبر واتساب' => 'whatsapp_draft',
        'ارسال مسودة العقد لكم عبر واتساب' => 'whatsapp_draft',
        'توثيق العقد في إيجار' => 'ejar_authenticated',
        'توثيق العقد في ايجار' => 'ejar_authenticated',
    ];

    /**
     * @return array{
     *     status: string,
     *     status_label: string,
     *     status_type: string,
     *     status_id: int|null,
     *     status_color: string|null,
     *     status_description: string|null
     * }
     */
    public static function for(?Contract $contract): array
    {
        if ($contract === null) {
            return self::empty();
        }

        $useDraft = (bool) $contract->is_draft && $contract->draft_contract_status_id;

        if ($useDraft) {
            $row = $contract->relationLoaded('draftContractStatus')
                ? $contract->draftContractStatus
                : $contract->draftContractStatus()->first();

            return self::fromRow(
                statusType: 'draft',
                id: $contract->draft_contract_status_id ? (int) $contract->draft_contract_status_id : null,
                name: $row?->name,
                color: $row?->color,
                description: $row?->description,
                defaultLabel: 'مسودة',
                defaultKey: 'draft'
            );
        }

        $row = $contract->relationLoaded('contractStatus')
            ? $contract->contractStatus
            : $contract->contractStatus()->first();

        return self::fromRow(
            statusType: 'contract',
            id: $contract->contract_status_id ? (int) $contract->contract_status_id : null,
            name: $row?->name,
            color: $row?->color,
            description: $row?->description,
            defaultLabel: 'قيد المراجعة',
            defaultKey: 'under_review'
        );
    }

    /**
     * Journey steps for «متى أستلم العقد؟» UI.
     *
     * @return list<array{key: string, status: string, status_label: string, description: string, state: string}>
     */
    public static function journey(?Contract $contract): array
    {
        $current = self::resolveJourneyKey($contract);

        $steps = [
            [
                'key' => 'paid',
                'status' => 'paid',
                'status_label' => 'تم الدفع',
                'description' => 'تم استلام المقابل المالي',
            ],
            [
                'key' => 'received_by_employee',
                'status' => 'received_by_employee',
                'status_label' => 'مستلم من الموظف',
                'description' => 'الحالة الآن - يراجع فريقنا بيانات طلبك',
            ],
            [
                'key' => 'whatsapp_draft',
                'status' => 'whatsapp_draft',
                'status_label' => 'إرسال مسودة العقد لكم عبر واتساب',
                'description' => 'تصلك المسودة للاطلاع والمراجعة قبل التوثيق',
            ],
            [
                'key' => 'ejar_authenticated',
                'status' => 'ejar_authenticated',
                'status_label' => 'توثيق العقد في إيجار',
                'description' => 'يُوثّق العقد ويصبح جاهزاً للتحميل ✓',
            ],
        ];

        $order = array_column($steps, 'key');
        $currentIndex = array_search($current, $order, true);
        if ($currentIndex === false) {
            $currentIndex = self::isPaid($contract) ? 0 : -1;
        }

        foreach ($steps as $i => &$step) {
            if ($i < $currentIndex) {
                $step['state'] = 'completed';
            } elseif ($i === $currentIndex) {
                $step['state'] = 'current';
            } else {
                $step['state'] = 'pending';
            }
        }
        unset($step);

        return $steps;
    }

    public static function journeyStatus(?Contract $contract): string
    {
        return self::resolveJourneyKey($contract);
    }

    public static function journeyStatusLabel(?Contract $contract): string
    {
        return self::journeyLabel(self::resolveJourneyKey($contract));
    }

    /**
     * @return array<string, string>
     */
    public static function firebaseData(Contract $contract): array
    {
        $payload = self::for($contract);
        $currentJourney = self::resolveJourneyKey($contract);

        return [
            'type' => 'contract_status_changed',
            'contract_id' => (string) $contract->id,
            'contract_uuid' => (string) ($contract->uuid ?? ''),
            'status' => (string) $payload['status'],
            'status_label' => (string) $payload['status_label'],
            'status_type' => (string) $payload['status_type'],
            'status_id' => $payload['status_id'] !== null ? (string) $payload['status_id'] : '',
            'status_color' => (string) ($payload['status_color'] ?? ''),
            'status_description' => (string) ($payload['status_description'] ?? ''),
            'is_draft' => $contract->is_draft ? '1' : '0',
            'journey_status' => (string) $currentJourney,
            'journey_status_label' => (string) self::journeyLabel($currentJourney),
        ];
    }

    public static function keyFromName(?string $name, string $fallback = 'unknown'): string
    {
        if ($name === null || trim($name) === '') {
            return $fallback;
        }

        $trimmed = trim($name);
        if (isset(self::NAME_TO_KEY[$trimmed])) {
            return self::NAME_TO_KEY[$trimmed];
        }

        if (str_contains($trimmed, 'واتس')) {
            return 'whatsapp_draft';
        }
        if (str_contains($trimmed, 'توثيق') || str_contains($trimmed, 'إيجار') || str_contains($trimmed, 'ايجار')) {
            return 'ejar_authenticated';
        }
        if (str_contains($trimmed, 'مستلم')) {
            return 'received_by_employee';
        }
        if (str_contains($trimmed, 'دفع')) {
            return 'paid';
        }

        return $fallback;
    }

    /**
     * @return array{
     *     status: string,
     *     status_label: string,
     *     status_type: string,
     *     status_id: int|null,
     *     status_color: string|null,
     *     status_description: string|null
     * }
     */
    private static function fromRow(
        string $statusType,
        ?int $id,
        ?string $name,
        ?string $color,
        ?string $description,
        string $defaultLabel,
        string $defaultKey
    ): array {
        $label = $name !== null && trim($name) !== '' ? trim($name) : $defaultLabel;
        $key = self::keyFromName($name, $id ? "{$statusType}_{$id}" : $defaultKey);

        return [
            'status' => $key,
            'status_label' => $label,
            'status_type' => $statusType,
            'status_id' => $id,
            'status_color' => $color,
            'status_description' => $description,
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     status_label: string,
     *     status_type: string,
     *     status_id: int|null,
     *     status_color: string|null,
     *     status_description: string|null
     * }
     */
    private static function empty(): array
    {
        return [
            'status' => 'unknown',
            'status_label' => 'غير محدد',
            'status_type' => 'contract',
            'status_id' => null,
            'status_color' => null,
            'status_description' => null,
        ];
    }

    private static function resolveJourneyKey(?Contract $contract): string
    {
        if ($contract === null) {
            return 'paid';
        }

        $statusPayload = self::for($contract);
        $key = $statusPayload['status'];

        if (in_array($key, ['ejar_authenticated', 'completed'], true)) {
            return 'ejar_authenticated';
        }
        if ($key === 'whatsapp_draft') {
            return 'whatsapp_draft';
        }
        if (in_array($key, ['received', 'received_by_employee'], true)
            || (int) $contract->contract_status_id === ContractStatus::RECEIVED_ID
            || $contract->receivedContract !== null
        ) {
            // Advance past received if WhatsApp draft file exists or status already beyond.
            if (self::hasWhatsAppDraft($contract)) {
                return 'whatsapp_draft';
            }

            return 'received_by_employee';
        }

        if (self::hasWhatsAppDraft($contract)) {
            return 'whatsapp_draft';
        }

        if (self::isPaid($contract)) {
            return 'paid';
        }

        return 'paid';
    }

    private static function isPaid(?Contract $contract): bool
    {
        if ($contract === null) {
            return false;
        }

        return (bool) $contract->is_completed;
    }

    private static function hasWhatsAppDraft(Contract $contract): bool
    {
        return filled($contract->draft_before_paid) || filled($contract->draft_after_paid);
    }

    private static function journeyLabel(string $key): string
    {
        return match ($key) {
            'paid' => 'تم الدفع',
            'received_by_employee' => 'مستلم من الموظف',
            'whatsapp_draft' => 'إرسال مسودة العقد لكم عبر واتساب',
            'ejar_authenticated' => 'توثيق العقد في إيجار',
            default => 'قيد المتابعة',
        };
    }
}
