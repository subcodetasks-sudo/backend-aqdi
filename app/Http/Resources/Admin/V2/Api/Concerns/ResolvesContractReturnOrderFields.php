<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

use App\Models\RefundableContract;
use App\Services\Admin\RefundableContractService;

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
        $isReturn = (int) $contract->contract_status_id === RefundableContractService::RETURN_CONTRACT_STATUS_ID;

        $refund = $this->resolveRefundableContractRow();
        $refundAmount = $refund?->refund_amount;

        return [
            'return_contract' => $isReturn,
            'draft_contract_number' => $isReturn
                ? str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT)
                : null,
            'refund_amount' => $refundAmount !== null && $refundAmount !== ''
                ? round((float) $refundAmount, 2)
                : null,
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
