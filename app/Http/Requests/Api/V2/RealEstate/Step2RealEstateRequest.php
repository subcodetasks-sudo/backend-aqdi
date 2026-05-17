<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\RealEstate;
use App\Support\DateInputNormalizer;
use Illuminate\Contracts\Validation\Validator;

/**
 * V2 real-estate step 2 (owner data; formerly step 3).
 */
class Step2RealEstateRequest extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('property_owner_dob_day') && $this->filled('property_owner_dob_hijri_day')) {
            $this->merge([
                'property_owner_dob_day' => $this->input('property_owner_dob_hijri_day'),
                'property_owner_dob_month' => $this->input('property_owner_dob_hijri_month'),
                'property_owner_dob_year' => $this->input('property_owner_dob_hijri_year'),
            ]);
        }

        if ($this->filled('property_owner_dob') && ! $this->filled('property_owner_dob_day')) {
            $raw = (string) $this->input('property_owner_dob');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'property_owner_dob_day' => (int) $parts[0],
                    'property_owner_dob_month' => (int) $parts[1],
                    'property_owner_dob_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('property_owner_dob_hijri') && ! $this->filled('property_owner_dob_day')) {
            $raw = (string) $this->input('property_owner_dob_hijri');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'property_owner_dob_day' => (int) $parts[0],
                    'property_owner_dob_month' => (int) $parts[1],
                    'property_owner_dob_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('dob_of_property_owner_agent') && ! $this->filled('dob_of_property_owner_agent_day')) {
            $raw = (string) $this->input('dob_of_property_owner_agent');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'dob_of_property_owner_agent_day' => (int) $parts[0],
                    'dob_of_property_owner_agent_month' => (int) $parts[1],
                    'dob_of_property_owner_agent_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('agency_instrument_date_of_property_owner') && ! $this->filled('agency_instrument_date_of_property_owner_day')) {
            $mysql = DateInputNormalizer::toMysqlDate((string) $this->input('agency_instrument_date_of_property_owner'));
            if ($mysql !== null) {
                $p = DateInputNormalizer::splitMysqlDate($mysql);
                $this->merge([
                    'agency_instrument_date_of_property_owner_day' => (int) $p['day'],
                    'agency_instrument_date_of_property_owner_month' => (int) $p['month'],
                    'agency_instrument_date_of_property_owner_year' => (int) $p['year'],
                ]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:real_estates,id',
            'type_dob_property_owner' => 'nullable|in:hijri,gregorian',
            'type_dob_property_owner_agent' => 'nullable|in:hijri,gregorian',
            'type_agency_instrument_date_of_property_owner' => 'nullable|in:hijri,gregorian',
            'name_real_estate' => 'nullable|string|max:255',
            'name_owner' => 'required|string',
            'property_owner_id_num' => 'required|min:10',
            'property_owner_dob_day' => ['nullable'],
            'property_owner_dob_month' => ['nullable'],
            'property_owner_dob_year' => ['nullable'],
            'property_owner_mobile' => 'required|min:10|regex:/^05[0-9]{8}$/',
            'property_owner_iban' => 'nullable|min:22',
            'add_legal_agent_of_owner' => 'required',
            'id_num_of_property_owner_agent' => 'nullable|min:10',
            'dob_of_property_owner_agent_day' => 'nullable',
            'dob_of_property_owner_agent_month' => 'nullable',
            'dob_of_property_owner_agent_year' => 'nullable',
            'mobile_of_property_owner_agent' => 'nullable|min:10|regex:/^05[0-9]{8}$/',
            'agency_number_in_instrument_of_property_owner' => 'nullable|string|max:255',
            'agency_instrument_date_of_property_owner_day' => 'nullable',
            'agency_instrument_date_of_property_owner_month' => 'nullable|integer|between:1,12',
            'agency_instrument_date_of_property_owner_year' => 'nullable|integer|min:1900|max:2100',
            'copy_of_the_authorization_or_agency' => 'nullable|mimes:jpg,jpeg,png,pdf',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $add = $this->input('add_legal_agent_of_owner');
            $hasAgent = in_array((string) $add, ['1', 'true'], true)
                || $add === 1
                || $add === true;
            if (! $hasAgent) {
                return;
            }
            if ($this->hasFile('copy_of_the_authorization_or_agency')) {
                return;
            }
            $realEstate = RealEstate::query()->find($this->input('id'));
            if ($realEstate?->copy_of_the_authorization_or_agency) {
                return;
            }
            $validator->errors()->add(
                'copy_of_the_authorization_or_agency',
                'نسخة من التوكيل أو الوكالة مطلوبة عند وجود وكيل للمالك.'
            );
        });
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقار مطلوب.',
            'id.exists' => 'العقار المحدد غير موجود.',
            'type_dob_property_owner.in' => 'نوع تاريخ الميلاد للمالك غير صالح.',
            'type_dob_property_owner_agent.in' => 'نوع تاريخ الميلاد لوكيل المالك غير صالح.',
            'name_real_estate.required' => 'اسم العقار مطلوب.',
            'name_real_estate.max' => 'اسم العقار يجب ألا يزيد عن 255 حرفاً.',
            'name_owner.required' => 'اسم المالك مطلوب.',
            'property_owner_id_num.required' => 'رقم هوية المالك مطلوب.',
            'property_owner_id_num.min' => 'رقم هوية المالك لا يقل عن 10 أرقام.',
            'property_owner_mobile.required' => 'رقم جوال المالك مطلوب.',
            'property_owner_mobile.regex' => 'رقم جوال المالك يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.',
            'add_legal_agent_of_owner.required' => 'تحديد وجود وكيل قانوني مطلوب.',
            'copy_of_the_authorization_or_agency.mimes' => 'نسخة التوكيل يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
        ];
    }
}
