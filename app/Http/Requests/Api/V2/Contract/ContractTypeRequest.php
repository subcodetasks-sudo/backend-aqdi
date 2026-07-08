<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use App\Models\RealEstate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ContractTypeRequest extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('instrument_type')) {
            $this->merge([
                'instrument_type' => Contract::normalizeInstrumentType($this->input('instrument_type')),
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
            'contract_type' => ['required', Rule::in(Contract::contractTypes())],
            'instrument_type' => ['nullable', Rule::in(Contract::instrumentTypes())],
            'is_real' => 'nullable|boolean',
            'real_id' => [
                Rule::requiredIf(fn () => (bool) $this->input('is_real')),
                'nullable',
                'exists:real_estates,id',
            ],
            'real_units_id' => [
                Rule::requiredIf(fn () => (bool) $this->input('is_real')),
                'nullable',
                'exists:real_units,id',
            ],
          
        ];
    }

    public function messages(): array
    {
        return $this->contractV2ArabicMessages([
            'contract_type',
            'instrument_type',
            'is_real',
            'real_id',
            'real_units_id',
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! in_array($this->input('instrument_type'), ['electronic', 'strong_argument'], true)) {
                return;
            }

            if (! (bool) $this->input('is_real')) {
                return;
            }

            $real = RealEstate::query()->find($this->input('real_id'));
            if (! $real || ! $real->hasResolvableUnitsCount(
                $this->filled('real_units_id') ? (int) $this->input('real_units_id') : null
            )) {
                $validator->errors()->add(
                    'number_of_units_in_realestate',
                    'عدد الوحدات غير محدد في العقار المرتبط بالعقد.'
                );
            }
        });
    }
}

