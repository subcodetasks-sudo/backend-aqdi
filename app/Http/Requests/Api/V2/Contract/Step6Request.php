<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Support\ContractStartingDateInput;
use Illuminate\Contracts\Validation\Validator;

class Step6Request extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        ContractStartingDateInput::prepareRequest($this);

        $legacy = $this->input('tenant_role_id');
        if (($legacy !== null && $legacy !== '') && ! $this->filled('tenant_role_ids')) {
            $this->merge([
                'tenant_role_ids' => [(int) $legacy],
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'contract_starting_date_day' => 'nullable',
            'contract_starting_date_month' => 'nullable',
            'contract_starting_date_year' => 'nullable',
            'type_contract_starting_date' => 'nullable|in:hijri,gregorian',
            'contract_term_in_years' => 'required|exists:contract_periods,id',
            'annual_rent_amount_for_the_unit' => 'required|numeric',
            'payment_type_id' => 'required|exists:payment_types,id',
            'conditions' => 'required|boolean',
            'other_conditions' => 'required_if:conditions,1|string|max:255',
            'additional_terms' => 'nullable|boolean',
             'tenant_roles' => 'nullable|boolean',
            'tenant_role_id' => 'nullable|integer|exists:tenant_roles,id',
            'tenant_role_ids' => 'nullable|array',
            'tenant_role_ids.*' => 'integer|exists:tenant_roles,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach (ContractStartingDateInput::validationErrors($this) as $key => $msgs) {
                foreach ($msgs as $m) {
                    $v->errors()->add($key, $m);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقد مطلوب.',
            'id.exists' => 'العقد المحدد غير موجود.',
            'contract_term_in_years.required' => 'مدة العقد مطلوبة.',
            'annual_rent_amount_for_the_unit.required' => 'قيمة الإيجار السنوي مطلوبة.',
            'payment_type_id.required' => 'نوع الدفع مطلوب.',
            'conditions.required' => 'حقل الشروط مطلوب.',
            'other_conditions.required_if' => 'حقل شروط أخرى مطلوب عندما تكون الشروط مفعلة.',
        ];
    }
}

