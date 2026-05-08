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
        $ownerEndowment = RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT;

        return [
           
            'real_id'            => 'nullable|exists:contracts,id',
            'instrument_type'    => ['nullable', Rule::in(Contract::instrumentTypes())],
            'number_of_floors'   => 'required',
            'property_type_id'   => 'required|exists:rea_estat_types,id',
            'property_usages_id' => [
                'nullable',
                Rule::requiredIf(in_array($instrumentType, ['electronic', 'strong_argument', $ownerEndowment], true)),
                'exists:rea_estat_usages,id',
            ],

            'image_instrument'   => [
                'nullable',
                'image',
                Rule::requiredIf(
                    in_array($instrumentType, ['electronic', 'electronic_deed_from_the_ministry_of_justice', $ownerEndowment], true)
                ),
            ],

            'image_address'      => 'nullable|image',
            'instrument_history' => 'nullable|date',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
            'age_of_the_property'            => 'nullable|integer|min:0',
            'number_of_units_per_floor'      => 'nullable|string|max:255',
            'number_of_units_in_realestate'  => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($instrumentType === $ownerEndowment),
            ],
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
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
            'copy_of_the_endowment_registration_certificate.required' => 'صورة من شهادة تسجيل الوقف مطلوبة.',
            'copy_of_the_trusteeship_deed.required' => 'صورة من صك النظارة مطلوبة.',
            'copy_of_guardians_power_of_attorney_for_agent.required' => 'صورة من وكالة النظار للوكيل مطلوبة عند وجود أكثر من ناظر.',
            'copy_of_the_endowment_registration_certificate.mimes' => 'نسخة شهادة تسجيل الوقف يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_the_trusteeship_deed.mimes' => 'نسخة صك النظارة يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_guardians_power_of_attorney_for_agent.mimes' => 'نسخة وكالة النظار يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
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