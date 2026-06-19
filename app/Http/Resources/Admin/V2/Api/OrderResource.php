<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractPaymentForAdmin;
use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractReturnAcceptance;
use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractReturnOrderFields;
use App\Models\ReceivedContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    use ResolvesContractPaymentForAdmin;
    use ResolvesContractReturnAcceptance;
    use ResolvesContractReturnOrderFields;

    public function toArray(Request $request): array
    {
        $receivedContract = $this->resolveReceivedContractRow();
        $payment = $this->contractPaymentFields();

        // True iff `received_contracts.contract_id` = this contract id (row exists).
        $receivedContractExists = $receivedContract !== null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type_trans,
            'contract_type_key' => $this->contract_type,
            'amount_payment' => $payment['amount_payment'],
            'is_paid' => $payment['is_paid'],
            'payment_status' => $payment['payment_status'],
            'payment_label_ar' => $payment['payment_label_ar'],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'status' => [
                 
                'name' => $this->contractStatus?->name,
                'color' => $this->contractStatus?->color,
            ],
            'is_received' => $receivedContractExists,
            'received_contract_exists' => $receivedContractExists,
            'employee_name' => $receivedContract?->employee?->name ?? 'لم يتم الاستلام',
            'user_id' => $this->user_id,
            'user_name' => $this->user->name ?? null,
            'user_mobile' => $this->user->mobile ?? null,
            'ownership' => $this->contract_ownership,
            'instrument_type' => $this->instrument_type_trans,
            'instrument_type_key' => $this->instrument_type,
            'is_completed' => (bool) $this->is_completed,
            'is_draft' => (bool) $this->is_draft,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ...$this->returnAcceptanceFields(),
            ...$this->returnOrderFields(),
        ];
    }

    /**
     * Row from `received_contracts` for this contract id (via contract_id), with employee when loaded from DB.
     */
    private function resolveReceivedContractRow(): ?ReceivedContract
    {
        if ($this->relationLoaded('receivedContract')) {
            return $this->receivedContract;
        }

        return ReceivedContract::query()
            ->where('contract_id', $this->resource->getKey())
            ->with('employee')
            ->first();
    }
}
