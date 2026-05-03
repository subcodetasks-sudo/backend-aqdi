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
        $instrumentType = $this->input('instrument_type');

        $aliases = [
            'electronic_deed_from_the_ministry_of_justice' => 'electronic',
            'electronic_deed' => 'electronic',
        ];

        if (is_string($instrumentType) && isset($aliases[$instrumentType])) {
            $this->merge([
                'instrument_type' => $aliases[$instrumentType],
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
            'contract_type' => ['required', Rule::in(['housing', 'commercial'])],
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
        return [
            'contract_type.required' => 'نوع العقد مطلوب.',
            'contract_type.in' => 'نوع العقد غير صالح.',
            'real_id.required' => 'العقار مطلوب عند اختيار عقد على عقار موجود.',
            'real_id.exists' => 'العقار المحدد غير موجود.',
            // 'real_units_id.required' => 'الوحدة مطلوبة عند اختيار عقد على عقار موجود.',
            // 'real_units_id.exists' => 'الوحدة المحددة غير موجودة.',
            // 'property_type_id.required' => 'نوع العقار مطلوب.',
            // 'property_type_id.exists' => 'نوع العقار غير موجود.',
            // 'number_of_floors.required' => 'عدد الأدوار مطلوب.',
            // 'property_usages_id.required_if' => 'استخدام العقار مطلوب.',
            // 'number_of_units_in_realestate.required' => 'عدد الوحدات مطلوب.',
            // 'number_of_units_in_realestate.integer' => 'عدد الوحدات يجب أن يكون رقمًا صحيحًا.',
            // 'image_instrument.required_if' => 'صورة الصك مطلوبة عند اختيار صك إلكتروني.',
            // 'image_instrument.image' => 'حقل صورة الصك يجب أن يكون صورة.',
        ];
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
            if (! $real || $real->number_of_units_in_realestate === null || $real->number_of_units_in_realestate === '') {
                $validator->errors()->add(
                    'number_of_units_in_realestate',
                    'عدد الوحدات غير محدد في العقار المرتبط بالعقد.'
                );
            }
        });
    }
}

