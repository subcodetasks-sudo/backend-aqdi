<?php

namespace App\Http\Requests\Admin;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractPaidByEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('employee_id');
        $this->request->remove('is_paid');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contract_uuid' => [
                'required',
                'string',
                'max:64',
                Rule::exists(Contract::class, 'uuid')->where(fn ($q) => $q->where('is_delete', false)),
            ],
            'customer_mobile' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
