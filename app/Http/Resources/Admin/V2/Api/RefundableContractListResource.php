<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Models\Payment;
use App\Services\Admin\RefundableContractService;
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
            'draft_contract_number' => $contract ? str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT) : null,
            'contract_id' => $this->contract_id,
            'contract_uuid' => $uuid,
            'customer_mobile' => $contract?->user?->mobile,
            'customer_name' => $contract?->user
                ? trim(($contract->user->fname ?? '').' '.($contract->user->lname ?? ''))
                : null,
            'contract_type' => $contract?->contract_type_trans,
            'contract_type_key' => $contract?->contract_type,
            'instrument_type' => $contract?->instrument_type_trans,
            'instrument_type_key' => $contract?->instrument_type,
            'contract_status_id' => $contract?->contract_status_id,
            'contract_status' => $contract?->contractStatus ? [
                'id' => $contract->contractStatus->id,
                'name' => $contract->contractStatus->name,
                'color' => $contract->contractStatus->color,
            ] : null,
            'is_return_order' => $contract?->contract_status_id === RefundableContractService::RETURN_CONTRACT_STATUS_ID,
            'payment_amount' => $this->resolvePaymentAmount($uuid),
            'refund_amount' => (float) $this->refund_amount,
            'is_refunded' => (bool) $this->is_refunded,
            'refunded_status' => [
                'refunded' => (bool) $this->is_refunded,
                'label_ar' => $this->is_refunded ? 'تم الاسترجاع' : 'لم يتم الاسترجاع',
            ],
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

}
