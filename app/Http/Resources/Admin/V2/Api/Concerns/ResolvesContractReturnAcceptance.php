<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

trait ResolvesContractReturnAcceptance
{
    /**
     * @return array<string, mixed>
     */
    protected function returnAcceptanceFields(): array
    {
        $employee = $this->relationLoaded('acceptRetrunContractEmployee')
            ? $this->acceptRetrunContractEmployee
            : null;

        return [
            'accept_retrun_contract' => (bool) $this->accept_retrun_contract,
            'accept_retrun_contract_employee_id' => $this->accept_retrun_contract_employee_id,
            'accept_retrun_contract_employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->name,
            ] : null,
        ];
    }
}
