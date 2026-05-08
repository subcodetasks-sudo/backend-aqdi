<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\RealEstate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateStep1RealEstateRequest extends BaseApiV2Request
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
        $instrumentTypes = RealEstate::instrumentTypes();
        $instrumentType = $this->input('instrument_type');

        return [
            'id' => 'required|exists:real_estates,id',
            'name_real_estate' => 'nullable|string|max:255',
            'contract_ownership' => 'required|in:owner,tenant',
            'contract_type' => 'required|in:housing,commercial',
            'instrument_number' => [Rule::requiredIf($instrumentType === 'electronic')],
            'instrument_history' => [Rule::requiredIf($instrumentType === 'electronic')],
            'real_estate_registry_number' => [Rule::requiredIf($instrumentType === 'strong_argument')],
            'date_first_registration' => [Rule::requiredIf($instrumentType === 'strong_argument')],
            'property_type_id' => 'required|exists:rea_estat_types,id',
            'property_owner_is_deceased' => 'required|boolean',
            'number_of_floors' => 'required',
            'instrument_type' => ['nullable', Rule::in($instrumentTypes), 'required_if:property_owner_is_deceased,1'],
            'property_usages_id' => [
                'nullable',
                Rule::requiredIf(in_array($instrumentType, [
                    'electronic',
                    'strong_argument',
                    RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT,
                ], true)),
                'exists:rea_estat_usages,id',
            ],
            'number_of_units_in_realestate' => [
                Rule::requiredIf(in_array($instrumentType, [
                    'electronic',
                    'strong_argument',
                    RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT,
                ], true)),
                'nullable',
                'integer',
            ],
            'image_instrument' => 'nullable|image',
            'image_address' => 'nullable|image',
            'age_of_the_property' => 'nullable|integer|min:0',
            'number_of_units_per_floor' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
            'copy_of_the_endowment_registration_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'copy_of_the_trusteeship_deed' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'is_multiple_trusteeship_deed_copy' => 'nullable|boolean',
            'copy_of_guardians_power_of_attorney_for_agent' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقار مطلوب.',
            'id.exists' => 'العقار المحدد غير موجود.',
            'copy_of_the_endowment_registration_certificate.mimes' => 'نسخة شهادة تسجيل الوقف يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_the_trusteeship_deed.mimes' => 'نسخة صك النظارة يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_guardians_power_of_attorney_for_agent.mimes' => 'نسخة وكالة النظار يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $instrumentType = $this->input('instrument_type');
            $ownerEndowment = RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT;

            $real = RealEstate::query()->find($this->input('id'));
            if (! $real) {
                return;
            }

            if ($instrumentType === 'electronic'
                && ! $this->hasFile('image_instrument')
                && empty($real->image_instrument)) {
                $validator->errors()->add('image_instrument', 'صورة الصك مطلوبة.');
            }

            if ($instrumentType === $ownerEndowment) {
                $checks = [
                    'image_instrument' => [$real->image_instrument, 'صورة الصك مطلوبة.'],
                    'copy_of_the_endowment_registration_certificate' => [
                        $real->copy_of_the_endowment_registration_certificate,
                        'صورة من شهادة تسجيل الوقف مطلوبة.',
                    ],
                    'copy_of_the_trusteeship_deed' => [$real->copy_of_the_trusteeship_deed, 'صورة من صك النظارة مطلوبة.'],
                ];
                foreach ($checks as $field => [$stored, $message]) {
                    if (! $this->hasFile($field) && empty($stored)) {
                        $validator->errors()->add($field, $message);
                    }
                }

                if ($this->boolean('is_multiple_trusteeship_deed_copy')
                    && ! $this->hasFile('copy_of_guardians_power_of_attorney_for_agent')
                    && empty($real->copy_of_guardians_power_of_attorney_for_agent)) {
                    $validator->errors()->add(
                        'copy_of_guardians_power_of_attorney_for_agent',
                        'صورة من وكالة النظار للوكيل مطلوبة عند وجود أكثر من ناظر.'
                    );
                }
            }
        });
    }
}
