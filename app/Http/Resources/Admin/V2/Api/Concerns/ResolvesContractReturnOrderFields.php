<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

use App\Models\RefundableContract;

trait ResolvesContractReturnOrderFields
{
    /**
     * @return array{
     *     return_contract: bool,
     *     draft_contract_number: string|null,
     *     refund_amount: float|null
     * }
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

        return [
            'draft_contract_number' => $draftContractNumber,
            'refund_amount' => $refundAmountValue,
            'return_contract' => $draftContractNumber !== null && $refundAmountValue !== null,
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
