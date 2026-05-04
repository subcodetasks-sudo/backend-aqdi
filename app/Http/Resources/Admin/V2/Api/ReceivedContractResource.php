<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Enums\ReceivedContractStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivedContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workflowStatus = $this->status instanceof ReceivedContractStatus
            ? $this->status
            : ReceivedContractStatus::tryFrom((string) $this->status) ?? ReceivedContractStatus::Pending;

        $receiptComplete = $workflowStatus === ReceivedContractStatus::Finish;

        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'employee_id' => $this->employee_id,
            'status' => $workflowStatus->value,
            'date_of_received' => $this->date_of_received?->format('Y-m-d'),
            'notes' => $this->notes,
            'employeeId' => $this->employee?->id,
            'employeeName' => $this->employee?->name,
            'employeeEmail' => $this->employee?->email,
            'employeePhone' => $this->employee?->phone,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'receipt_status' => $receiptComplete ? 'received' : 'pending',
            'receipt_status_label_ar' => $receiptComplete ? 'مستلم' : 'لم يُستلم بعد',
        ];
    }
}
