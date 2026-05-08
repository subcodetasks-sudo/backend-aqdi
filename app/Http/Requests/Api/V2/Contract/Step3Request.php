<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use App\Support\DateInputNormalizer;
use App\Support\HijriDobParts;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Stringable;

class Step3Request extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        foreach ([
            'property_owner_dob_day',
            'property_owner_dob_month',
            'property_owner_dob_year',
            'dob_of_property_owner_agent_day',
            'dob_of_property_owner_agent_month',
            'dob_of_property_owner_agent_year',
        ] as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }

        // Alias keys used by mobile clients / docs (same as Step3RealEstateRequest).
        if (! $this->filled('property_owner_dob_day') && $this->filled('property_owner_dob_day')) {
            $this->merge([
                'property_owner_dob_day' => $this->input('property_owner_dob_day'),
                'property_owner_dob_month' => $this->input('property_owner_dob_month'),
                'property_owner_dob_year' => $this->input('property_owner_dob_year'),
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

    /**
     * Builds DD-MM-YYYY from request parts after prepareForValidation (handles JSON ints, trimmed strings; ignores stray files).
     */
    public function resolvedPropertyOwnerDobString(): ?string
    {
        $day = $this->normalizedDobPart($this->input('property_owner_dob_day'));
        $month = $this->normalizedDobPart($this->input('property_owner_dob_month'));
        $year = $this->normalizedDobPart($this->input('property_owner_dob_year'));

        if ($day !== null && $month !== null && $year !== null) {
            return HijriDobParts::combine($day, $month, $year);
        }

        // Fallback: combined date only (parts merge did not run or failed).
        foreach (['property_owner_dob'] as $key) {
            $combined = $this->normalizedCombinedDobString($this->input($key));
            if ($combined !== null) {
                return $combined;
            }
        }

        return null;
    }

    private function normalizedCombinedDobString(mixed $raw): ?string
    {
        if ($raw instanceof UploadedFile || $raw === null || $raw === '') {
            return null;
        }
        if ($raw instanceof Stringable) {
            $raw = $raw->__toString();
        }
        if (! is_string($raw)) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }
        $parts = preg_split('/[-\/]/', $trimmed);
        if (count($parts) !== 3) {
            return null;
        }

        return HijriDobParts::combine($parts[0], $parts[1], $parts[2]);
    }

    private function normalizedDobPart(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return null;
        }
        if ($value instanceof Stringable) {
            $value = $value->__toString();
        }
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return null;
    }

    public function rules(): array
    {
        if ($this->isLeaseRenewalContract()) {
            return $this->leaseRenewalRules();
        }

        return [
            'id' => 'required|exists:contracts,id',
            'type_dob_property_owner' => 'nullable|in:hijri,gregorian',
            'type_dob_property_owner_agent' => 'nullable|in:hijri,gregorian',
            'type_agency_instrument_date_of_property_owner' => 'nullable|in:hijri,gregorian',
            'name_real_estate' => 'nullable|string|max:255',
            'name_owner' => 'required|string',
            'property_owner_id_num' => 'required|min:10',
            'property_owner_dob_day' => 'required',
            'property_owner_dob_month' => 'required',
            'property_owner_dob_year' => 'required',
            'property_owner_mobile' => 'required|min:10|regex:/^05[0-9]{8}$/',
            'property_owner_iban' => 'nullable|min:22',
            'add_legal_agent_of_owner' => 'required',
            'id_num_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10',
            'dob_of_property_owner_agent_day' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'dob_of_property_owner_agent_month' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'dob_of_property_owner_agent_year' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'mobile_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10|regex:/^05[0-9]{8}$/',
            'copy_of_the_authorization_or_agency' => 'nullable',
        ];
    }

    /**
     * lease_renewal: requested edits are required; other fields optional.
     */
    private function leaseRenewalRules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'type_dob_property_owner' => 'nullable|in:hijri,gregorian',
            'type_dob_property_owner_agent' => 'nullable|in:hijri,gregorian',
            'type_agency_instrument_date_of_property_owner' => 'nullable|in:hijri,gregorian',
            'name_real_estate' => 'nullable|string|max:255',
            'name_owner' => 'nullable|string',
            'property_owner_id_num' => 'nullable|min:10',
            'property_owner_dob_day' => 'nullable',
            'property_owner_dob_month' => 'nullable',
            'property_owner_dob_year' => 'nullable',
            'property_owner_mobile' => 'nullable|min:10|regex:/^05[0-9]{8}$/',
            'property_owner_iban' => 'nullable|min:22',
            'add_legal_agent_of_owner' => 'nullable',
            'id_num_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10',
            'dob_of_property_owner_agent_day' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'dob_of_property_owner_agent_month' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'dob_of_property_owner_agent_year' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'mobile_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10|regex:/^05[0-9]{8}$/',
            'copy_of_the_authorization_or_agency' => 'nullable',
        ];
    }

    private function isLeaseRenewalContract(): bool
    {
        $contractId = $this->input('id');

        return $contractId
            && Contract::query()->whereKey($contractId)->value('instrument_type') === 'lease_renewal';
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
            $contract = Contract::query()->find($this->input('id'));
            if ($contract?->copy_of_the_authorization_or_agency) {
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
            'id_num_of_property_owner_agent.required_if' => 'رقم هوية الوكيل مطلوب.',
            'dob_of_property_owner_agent_day.required_if' => 'يوم تاريخ ميلاد الوكيل مطلوب.',
            'dob_of_property_owner_agent_day.between' => 'يوم تاريخ ميلاد الوكيل غير صالح.',
            'dob_of_property_owner_agent_month.required_if' => 'شهر تاريخ ميلاد الوكيل مطلوب.',
            'dob_of_property_owner_agent_month.between' => 'شهر تاريخ ميلاد الوكيل غير صالح.',
            'dob_of_property_owner_agent_year.required_if' => 'سنة تاريخ ميلاد الوكيل مطلوبة.',
            'dob_of_property_owner_agent_year.min' => 'سنة تاريخ ميلاد الوكيل غير صالحة.',
            'dob_of_property_owner_agent_year.max' => 'سنة تاريخ ميلاد الوكيل غير صالحة.',
            'mobile_of_property_owner_agent.required_if' => 'رقم جوال الوكيل مطلوب.',
            'copy_of_the_authorization_or_agency.mimes' => 'نسخة التوكيل يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'property_owner_dob_day.required' => 'يوم تاريخ ميلاد المالك مطلوب.',
            'property_owner_dob_month.required' => 'شهر تاريخ ميلاد المالك مطلوب.',
            'property_owner_dob_year.required' => 'سنة تاريخ ميلاد المالك مطلوبة.',
        ];
    }
}
