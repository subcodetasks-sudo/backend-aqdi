<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundableContractListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contract = $this->contract;
        $uuid = $contract?->uuid;

        return [
            'id' => $this->id,
            'order_number' => $contract ? str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT) : null,
            'contract_id' => $this->contract_id,
            'contract_uuid' => $uuid,
            'customer_mobile' => $contract?->user?->mobile,
            'customer_name' => $contract?->user
                ? trim(($contract->user->fname ?? '').' '.($contract->user->lname ?? ''))
                : null,
            'contract_type' => $contract?->contract_type_trans,
            'contract_type_key' => $contract?->contract_type,
            'contract_status' => $contract?->contractStatus ? [
                'id' => $contract->contractStatus->id,
                'name' => $contract->contractStatus->name,
                'color' => $contract->contractStatus->color,
            ] : null,
            'payment_amount' => $this->resolvePaymentAmount($uuid),
            'refund_amount' => (float) $this->refund_amount,
            'is_refunded' => $this->resolveIsRefunded($uuid),
            'requester' => [
                'id' => $this->employee_id,
                'name' => $this->employee?->name,
            ],
            'management_approval' => [
                'approved' => (bool) $this->admin_confirmed,
                'label_ar' => $this->admin_confirmed ? 'تم الموافقة' : 'لم تتم الموافقة',
            ],
            'has_draft_contract' => (bool) $this->has_draft_contract,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function resolvePaymentAmount(?string $contractUuid): ?float
    {
        if (! $contractUuid) {
            return null;
        }

        $amount = Payment::query()
            ->where('contract_uuid', $contractUuid)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->value('amount');

        return $amount !== null ? (float) $amount : null;
    }

    private function resolveIsRefunded(?string $contractUuid): bool
    {
        if (! $contractUuid) {
            return false;
        }

        return Payment::query()
            ->where('contract_uuid', $contractUuid)
            ->where('status', 'failed')
            ->exists();
    }
}
