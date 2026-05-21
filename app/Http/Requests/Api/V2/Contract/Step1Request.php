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
        parent::prepareForValidation();
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
        return $this->contractV2ArabicMessages([
            'id',
            'instrument_type',
            'number_of_floors',
            'property_type_id',
            'property_usages_id',
            'number_of_units_in_realestate',
            'number_of_units_per_floor',
            'image_instrument',
            'image_address',
            'image_instrument_from_the_front',
            'image_instrument_from_the_back',
            'instrument_history',
            'type_instrument_history',
            'type_date_first_registration',
            'age_of_the_property',
            'copy_of_the_endowment_registration_certificate',
            'copy_of_the_trusteeship_deed',
            'is_multiple_trusteeship_deed_copy',
            'latitude',
            'longitude',
            'lat',
            'lng',
            'property_place_id',
            'property_city_id',
            'neighborhood',
            'street',
            'building_number',
            'postal_code',
            'extra_figure',
        ]);
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
