<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\ContractPropertyAddressRules;
use App\Http\Requests\Api\V2\Concerns\NormalizesCoordinateInputs;
use App\Models\City;
use App\Models\Contract;
use App\Models\RealEstate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class Step1Request extends BaseApiV2Request
{
    use ContractPropertyAddressRules;
    use NormalizesCoordinateInputs;

    protected function prepareForValidation(): void
    {
        $this->normalizeCoordinateInputs();

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
        $effectiveInstrumentType = $this->effectiveInstrumentType();

        return [
            'id' => 'required|exists:contracts,id',
             'instrument_type' => ['nullable', Rule::in(Contract::instrumentTypes())],
             'number_of_floors' => [
                 Rule::requiredIf(! Contract::shouldSkipInitialSteps($effectiveInstrumentType)),
             ],
             'property_type_id' => [
                 'nullable',
                 'required_if:instrument_type,electronic,strong_argument',
                 Rule::exists('rea_estat_types', 'id'),
             ],
            'property_usages_id' => 'required_if:instrument_type,electronic,strong_argument',
            'number_of_units_in_realestate' => [
                Rule::requiredIf(function () {
                    if (! in_array($this->input('instrument_type'), ['electronic', 'strong_argument'], true)) {
                        return false;
                    }
                    $contract = Contract::query()->find($this->input('id'));

                    return $contract && ! $contract->real_id;
                }),
                'nullable',
                'integer',
            ],
            'image_instrument' => [
                'nullable',
                'image',
                Rule::requiredIf(
                    in_array(
                        $effectiveInstrumentType,
                        ['electronic', 'electronic_deed_from_the_ministry_of_justice', 'lease_renewal'],
                        true
                    )
                ),
            ],
            'image_address' => 'nullable|image',
            'instrument_history' => 'nullable|date',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
            'age_of_the_property' => 'nullable|integer|min:0',
            'number_of_units_per_floor' => 'nullable|string|max:255',
            'number_of_units_in_realestate' => 'nullable|string|max:255',
            'copy_of_the_endowment_registration_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'copy_of_the_trusteeship_deed' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'is_multiple_trusteeship_deed_copy' => 'nullable|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'image_instrument_from_the_back'=>'nullable',
            'image_instrument_from_the_front'=>'nullable',
            ...$this->contractPropertyAddressRules(),
        ];
    }

    private function effectiveInstrumentType(): ?string
    {
        if ($this->filled('instrument_type')) {
            return (string) $this->input('instrument_type');
        }

        $id = $this->input('id');
        if (! $id) {
            return null;
        }

        return Contract::query()->whereKey($id)->value('instrument_type');
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقد مطلوب.',
            'id.exists' => 'العقد المحدد غير موجود.',
         //   'contract_ownership.required' => 'نوع ملكية العقد مطلوب.',
            'contract_ownership.in' => 'نوع ملكية العقد غير صالح.',
            'instrument_type.in' => 'نوع الصك غير صالح.',
            'instrument_number.required_if' => 'رقم الصك مطلوب عند اختيار صك إلكتروني.',
            'instrument_history.required_if' => 'تاريخ الصك مطلوب عند اختيار صك إلكتروني.',
            'real_estate_registry_number.required_if' => 'رقم السجل العقاري مطلوب عند اختيار صك السجل العقاري.',
            'date_first_registration.required_if' => 'تاريخ أول تسجيل مطلوب عند اختيار صك السجل العقاري.',
            'property_type_id.required_if' => 'نوع العقار مطلوب في حالة الصك الإلكتروني أو السجل العقاري.',
            'property_type_id.exists' => 'نوع العقار غير موجود.',
            //'property_owner_is_deceased.required' => 'حالة مالك العقار مطلوبة.',
          //  'property_owner_is_deceased.boolean' => 'حالة مالك العقار يجب أن تكون نعم أو لا.',
            'number_of_floors.required' => 'عدد الأدوار مطلوب.',
            'property_usages_id.required_if' => 'استخدام العقار مطلوب.',
            'number_of_units_in_realestate.required' => 'عدد الوحدات مطلوب.',
            'number_of_units_in_realestate.integer' => 'عدد الوحدات يجب أن يكون رقمًا صحيحًا.',
            'image_instrument.required' => 'صورة الصك مطلوبة.',
            'image_instrument.required_if' => 'صورة الصك مطلوبة.',
             'image_address.image' => 'حقل صورة العنوان يجب أن يكون صورة.',
            'copy_of_the_endowment_registration_certificate.mimes' => 'نسخة شهادة تسجيل الوقف يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_the_trusteeship_deed.mimes' => 'نسخة صك الولاية يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'is_multiple_trusteeship_deed_copy.boolean' => 'حقل تعدد نسخ صك الولاية يجب أن يكون true أو false.',
            ...$this->contractPropertyAddressMessages(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('property_city_id') && $this->filled('property_place_id')) {
                $city = City::query()
                    ->whereKey($this->input('property_city_id'))
                    ->where('region_id', $this->input('property_place_id'))
                    ->exists();

                if (! $city) {
                    $validator->errors()->add('property_city_id', trans('api.city_not_include_region'));
                }
            }

            if (! in_array($this->input('instrument_type'), ['electronic', 'strong_argument'], true)) {
                return;
            }

            $contract = Contract::query()->find($this->input('id'));
            if (! $contract || ! $contract->real_id) {
                return;
            }

            $real = RealEstate::query()->find($contract->real_id);
            if (! $real || $real->number_of_units_in_realestate === null || $real->number_of_units_in_realestate === '') {
                $validator->errors()->add(
                    'number_of_units_in_realestate',
                    'عدد الوحدات غير محدد في العقار المرتبط بالعقد.'
                );
            }
        });
    }
}
