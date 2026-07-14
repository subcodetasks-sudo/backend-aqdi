<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\RealEstate\Concerns\NormalizesRealEstateInstrumentType;
use App\Http\Requests\Api\V2\RealEstate\Concerns\RealEstateLocationRules;
use App\Models\City;
use App\Models\RealEstate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * V2 real-estate step 1: same shape as {@see \App\Http\Requests\Api\V2\Contract\Step1Request}
 * but for creating a {@see RealEstate} (no contract id).
 */
class Step1RealEstateRequest extends BaseApiV2Request
{
    use NormalizesRealEstateInstrumentType;
    use RealEstateLocationRules;

    protected function prepareForValidation(): void
    {
        $this->normalizeCoordinateInputs();
        $this->normalizeInstrumentTypeInput();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $instrumentType = $this->input('instrument_type');
        $ownerEndowment = RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT;

        return array_merge([
            'real_id'            => 'nullable|exists:contracts,id',
            'instrument_type'    => ['nullable', Rule::in(RealEstate::instrumentTypes())],
            'number_of_floors'   => 'nullable',
            'contract_type'    => 'nullable|in:housing,commercial',
            'contract_ownership' => 'nullable|in:owner,tenant',
            'electricity_meter_ownership' => 'nullable|in:owner,tenant',
            'water_meter_ownership' => 'nullable|in:owner,tenant',
            'property_type_id'   => 'nullable|exists:rea_estat_types,id',
            'property_usages_id' => 'nullable|exists:rea_estat_usages,id',

            'image_instrument'   => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                Rule::requiredIf(in_array($instrumentType, ['electronic', $ownerEndowment], true)),
            ],

            'instrument_history' => 'nullable|date',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
            'age_of_the_property'            => 'nullable|integer|min:0',
            'number_of_units_per_floor'      => 'nullable|string|max:255',
            'number_of_units_in_realestate'  => 'nullable|string|max:255',
            'copy_of_the_endowment_registration_certificate' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                Rule::requiredIf($instrumentType === $ownerEndowment),
            ],
            'copy_of_the_trusteeship_deed' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                Rule::requiredIf($instrumentType === $ownerEndowment),
            ],
            'is_multiple_trusteeship_deed_copy' => 'nullable|boolean',
            'copy_of_guardians_power_of_attorney_for_agent' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                Rule::requiredIf(function () use ($instrumentType, $ownerEndowment) {
                    return $instrumentType === $ownerEndowment
                        && $this->boolean('is_multiple_trusteeship_deed_copy');
                }),
            ],
        ], $this->locationRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->filled('property_city_id') || ! $this->filled('property_place_id')) {
                return;
            }

            $valid = City::query()
                ->where('id', $this->input('property_city_id'))
                ->where('region_id', $this->input('property_place_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('property_city_id', trans('api.city_not_include_region'));
            }
        });
    }

    public function messages(): array
    {
        return array_merge([
            'property_type_id.exists'                 => 'نوع العقار غير موجود.',
            'property_usages_id.exists'               => 'استخدام العقار غير موجود.',
            'contract_type.required'                  => 'نوع العقد مطلوب.',
            'contract_type.in'                        => 'نوع العقد يجب أن يكون سكني أو تجاري.',
            'instrument_type.in'                      => 'نوع الصك غير صالح.',
            'number_of_units_in_realestate.string'    => 'عدد الوحدات يجب أن يكون نصًا.',
            'image_instrument.required'               => 'صورة الصك مطلوبة عند اختيار صك إلكتروني.',
            'image_instrument.mimes'                  => 'ملف الصك يجب أن يكون بصيغة jpg أو jpeg أو png أو webp أو pdf.',
            'copy_of_the_endowment_registration_certificate.required' => 'صورة من شهادة تسجيل الوقف مطلوبة.',
            'copy_of_the_trusteeship_deed.required' => 'صورة من صك النظارة مطلوبة.',
            'copy_of_guardians_power_of_attorney_for_agent.required' => 'صورة من وكالة النظار للوكيل مطلوبة عند وجود أكثر من ناظر.',
            'copy_of_the_endowment_registration_certificate.mimes' => 'نسخة شهادة تسجيل الوقف يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_the_trusteeship_deed.mimes' => 'نسخة صك النظارة يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_guardians_power_of_attorney_for_agent.mimes' => 'نسخة وكالة النظار يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
        ], $this->locationMessages());
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForCreate(int $userId): array
    {
        $payload = array_merge([
            'user_id'                        => $userId,
            'instrument_number'              => null,
            'number_of_units_in_realestate'  => $this->input('number_of_units_in_realestate'),
            'property_type_id'               => $this->input('property_type_id'),
            'property_usages_id'             => $this->input('property_usages_id'),
            'number_of_floors'               => $this->input('number_of_floors'),
            'age_of_the_property'            => $this->input('age_of_the_property'),
            'number_of_units_per_floor'      => $this->input('number_of_units_per_floor'),
            'step'                           => 1,
        ], $this->locationAttributesForPayload());

        if ($this->filled('contract_type')) {
            $payload['contract_type'] = $this->input('contract_type');
        }

        if ($this->filled('instrument_type')) {
            $payload['instrument_type'] = $this->input('instrument_type');
        }

        if ($this->filled('contract_ownership')) {
            $payload['contract_ownership'] = $this->input('contract_ownership');
        }

        if ($this->exists('electricity_meter_ownership')) {
            $value = $this->input('electricity_meter_ownership');
            $payload['electricity_meter_ownership'] = ($value === '' || $value === null) ? null : $value;
        }

        if ($this->exists('water_meter_ownership')) {
            $value = $this->input('water_meter_ownership');
            $payload['water_meter_ownership'] = ($value === '' || $value === null) ? null : $value;
        }

         if ($this->input('instrument_type') === 'electronic' && $this->filled('instrument_history')) {
            $payload['instrument_history'] = date('Y-m-d', strtotime((string) $this->input('instrument_history')));
            $payload['type_instrument_history'] = $this->input('type_instrument_history', 'hijri');
        }

        if ($this->input('instrument_type') === 'strong_argument' && $this->filled('date_first_registration')) {
            $payload['type_date_first_registration'] = $this->input('type_date_first_registration', 'hijri');
        }

        if ($this->hasFile('image_instrument')) {
            $payload['image_instrument'] = $this->file('image_instrument')
                ->store('images/real_estates', 'public');
        }

        if ($this->hasFile('image_address')) {
            $payload['image_address'] = $this->file('image_address')
                ->store('images/real_estates', 'public');
        }

        if ($this->input('instrument_type') === RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT) {
            $payload['is_multiple_trusteeship_deed_copy'] = $this->boolean('is_multiple_trusteeship_deed_copy');
            if ($this->hasFile('copy_of_the_endowment_registration_certificate')) {
                $payload['copy_of_the_endowment_registration_certificate'] = $this->file('copy_of_the_endowment_registration_certificate')
                    ->store('real_estates/endowment-registration-certificates', 'public');
            }
            if ($this->hasFile('copy_of_the_trusteeship_deed')) {
                $payload['copy_of_the_trusteeship_deed'] = $this->file('copy_of_the_trusteeship_deed')
                    ->store('real_estates/trusteeship-deeds', 'public');
            }
            if ($this->hasFile('copy_of_guardians_power_of_attorney_for_agent')) {
                $payload['copy_of_guardians_power_of_attorney_for_agent'] = $this->file('copy_of_guardians_power_of_attorney_for_agent')
                    ->store('real_estates/guardians-power-of-attorney', 'public');
            }
        }

        return $payload;
    }
}