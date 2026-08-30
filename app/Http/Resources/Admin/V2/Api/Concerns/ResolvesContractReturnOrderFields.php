<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

use App\Models\RefundableContract;
use App\Support\RefundableContractReference;

trait ResolvesContractReturnOrderFields
{
    /**
     * @return array<string, mixed>
     */
    protected function returnOrderFields(): array
    {
        $contract = $this->resource;
        $refund = $this->resolveRefundableContractRow();

        $refundAmount = $refund?->refund_amount;
        $refundAmountValue = $refundAmount !== null && $refundAmount !== ''
            ? round((float) $refundAmount, 2)
            : null;

        $draftContractNumber = $refund && $contract->getKey()
            ? str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT)
            : null;

        $approval = $this->refundManagementApprovalFields($refund);

        return [
            'contract_id' => $contract->getKey(),
            'draft_contract_number' => $draftContractNumber,
            'refund_amount' => $refundAmountValue,
            'return_contract' => $draftContractNumber !== null && $refundAmountValue !== null,
            'refund_id' => $refund?->id,
            'refundable_contract_id' => $refund?->id,
            'refund' => $refund ? [
                'id' => $refund->id,
                'refund_amount' => $refundAmountValue,
                'admin_confirmed' => $refund->admin_confirmed,
                'is_refunded' => (bool) $refund->is_refunded,
                'notes' => $refund->notes,
                'reference_number' => RefundableContractReference::for($refund),
                'management_approval' => $approval,
            ] : null,
            'refundable_contract' => $refund ? [
                'id' => $refund->id,
                'refund_amount' => $refundAmountValue,
                'admin_confirmed' => $refund->admin_confirmed,
                'is_refunded' => (bool) $refund->is_refunded,
            ] : null,
            'admin_confirmed' => $refund?->admin_confirmed,
            'management_approval' => $approval,
            'is_refunded' => $refund ? (bool) $refund->is_refunded : null,
            'customer_refunded' => $refund ? (bool) $refund->is_refunded : null,
            'refunded' => $refund ? (bool) $refund->is_refunded : null,
            'reference_number' => $refund ? RefundableContractReference::for($refund) : null,
            'refund_notes' => $refund?->notes,
        ];
    }

    /**
     * @return array{approved: bool|null, label_ar: string}|null
     */
    private function refundManagementApprovalFields(?RefundableContract $refund): ?array
    {
        if (! $refund) {
            return null;
        }

        if ($refund->admin_confirmed === null) {
            return [
                'approved' => null,
                'label_ar' => 'بانتظار الموافقة',
            ];
        }

        if ($refund->admin_confirmed === true || $refund->admin_confirmed === 1 || $refund->admin_confirmed === '1') {
            return [
                'approved' => true,
                'label_ar' => 'تم الموافقة',
            ];
        }

        return [
            'approved' => false,
            'label_ar' => 'لم تتم الموافقة',
        ];
    }

    private function resolveRefundableContractRow(): ?RefundableContract
    {
        if ($this->relationLoaded('refundableContract')) {
            return $this->refundableContract;
        }

        return RefundableContract::query()
            ->where('contract_id', $this->resource->getKey())
            ->latest('id')
            ->first();
    }
}
