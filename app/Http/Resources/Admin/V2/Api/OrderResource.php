<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Models\ReceivedContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receivedContract = $this->resolveReceivedContractRow();

        // True iff `received_contracts.contract_id` = this contract id (row exists).
        $receivedContractExists = $receivedContract !== null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type,
            'amount_payment' => $this->contract->payments->amount ?? 'لم يتم الدفع',
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
            'instrument_type' => $this->instrument_type,
            'is_completed' => (bool) $this->is_completed,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
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
