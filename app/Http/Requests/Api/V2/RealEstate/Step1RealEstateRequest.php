<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use App\Models\RealEstate;
use Illuminate\Validation\Rule;

/**
 * V2 real-estate step 1: same shape as {@see \App\Http\Requests\Api\V2\Contract\Step1Request}
 * but for creating a {@see RealEstate} (no contract id).
 */
class Step1RealEstateRequest extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        $instrumentType = $this->input('instrument_type');

        $aliases = [
            'electronic_deed_from_the_ministry_of_justice' => 'electronic',
            'electronic_deed'                              => 'electronic',
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
        $instrumentType = $this->input('instrument_type');

        return [
           
            'real_id'            => 'nullable|exists:contracts,id',
            'instrument_type'    => ['nullable', Rule::in(Contract::instrumentTypes())],
            'number_of_floors'   => 'required',
            'property_type_id'   => 'required|exists:rea_estat_types,id',
            'property_usages_id' => 'required_if:instrument_type,electronic,strong_argument',

            'image_instrument'   => [
                'nullable',
                'image',
                Rule::requiredIf(
                    in_array($instrumentType, ['electronic', 'electronic_deed_from_the_ministry_of_justice'], true)
                ),
            ],

            'image_address'      => 'nullable|image',
            'instrument_history' => 'nullable|date',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
            'age_of_the_property'            => 'nullable|integer|min:0',
            'number_of_units_per_floor'      => 'nullable|string|max:255',
            'number_of_units_in_realestate'  => 'nullable|string|max:255',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'property_type_id.required'               => 'نوع العقار مطلوب.',
            'property_type_id.exists'                 => 'نوع العقار غير موجود.',
            'number_of_floors.required'               => 'عدد الأدوار مطلوب.',
            'property_usages_id.required_if'          => 'استخدام العقار مطلوب.',
            'number_of_units_in_realestate.required'  => 'عدد الوحدات مطلوب.',
            'number_of_units_in_realestate.string'    => 'عدد الوحدات يجب أن يكون نصًا.',
            'image_instrument.required'               => 'صورة الصك مطلوبة عند اختيار صك إلكتروني.',
            'image_instrument.image'                  => 'حقل صورة الصك يجب أن يكون صورة.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForCreate(int $userId): array
    {
        $payload = [
            'user_id'                        => $userId,
             'number_of_units_in_realestate'  => $this->input('number_of_units_in_realestate'),
            'instrument_type'                => $this->input('instrument_type'),
            'property_type_id'               => $this->input('property_type_id'),
            'property_usages_id'             => $this->input('property_usages_id'),
            'number_of_floors'               => $this->input('number_of_floors'),
            'age_of_the_property'            => $this->input('age_of_the_property'),
            'number_of_units_per_floor'      => $this->input('number_of_units_per_floor'),
            'step'                           => 2,
        ];

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

        return $payload;
    }
}