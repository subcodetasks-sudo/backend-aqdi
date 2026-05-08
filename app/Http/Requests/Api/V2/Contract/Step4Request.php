<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use App\Support\HijriDobParts;

class Step4Request extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('tenant_dob') && ! $this->filled('tenant_dob_day')) {
            $parts = HijriDobParts::split((string) $this->input('tenant_dob'));
            if ($parts['day'] !== null && $parts['month'] !== null && $parts['year'] !== null) {
                $this->merge([
                    'tenant_dob_day' => (int) $parts['day'],
                    'tenant_dob_month' => (int) $parts['month'],
                    'tenant_dob_year' => (int) $parts['year'],
                ]);
            }
        }

        if ($this->filled('dobof_property_tenant_agent') && ! $this->filled('dobof_property_tenant_agent_day')) {
            $parts = HijriDobParts::split((string) $this->input('dobof_property_tenant_agent'));
            if ($parts['day'] !== null && $parts['month'] !== null && $parts['year'] !== null) {
                $this->merge([
                    'dobof_property_tenant_agent_day' => (int) $parts['day'],
                    'dobof_property_tenant_agent_month' => (int) $parts['month'],
                    'dobof_property_tenant_agent_year' => (int) $parts['year'],
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
        if ($this->isLeaseRenewalContract()) {
            return [
                'id' => 'required|exists:contracts,id',
                'notes_edits' => 'nullable|string|max:20000',
            ];
        }

        return [
            'id' => 'required|exists:contracts,id',
            'tenant_entity' => 'required|in:person,institution',
            'tenant_id_num' => 'nullable|required_if:tenant_entity,person|min:10',
            'tenant_dob' => 'nullable',
            'tenant_dob_day' => 'nullable|required_if:tenant_entity,person',
            'tenant_dob_month' => 'nullable|required_if:tenant_entity,person',
            'tenant_dob_year' => 'nullable|required_if:tenant_entity,person',
            'tenant_mobile' => 'nullable|required_if:tenant_entity,person|min:10|regex:/^05[0-9]{8}$/',
            'region_of_the_tenant_legal_agent' => 'nullable|required_if:tenant_entity,institution|exists:regions,id',
            'city_of_the_tenant_legal_agent' => 'nullable|required_if:tenant_entity,institution|exists:cities,id',
            'tenant_entity_unified_registry_number' => 'nullable|required_if:tenant_entity,institution',
            'authorization_type' => 'nullable|required_if:tenant_entity,institution',
            'copy_of_the_owner_record' => 'nullable|mimes:jpg,jpeg,png,pdf',
            'id_num_of_property_tenant_agent' => 'nullable|min:10',
            'mobile_of_property_tenant_agent' => 'nullable',
            'dobof_property_tenant_agent_day' => 'nullable',
            'dobof_property_tenant_agent_month' => 'nullable',
            'dobof_property_tenant_agent_year' => 'nullable',
            'type_tenant_dob' => 'nullable|in:hijri,gregorian',
            'type_dob_tenant_agent' => 'nullable|in:hijri,gregorian',
            'notes_edits' => 'nullable|string|max:20000',

        ];
    }

    private function isLeaseRenewalContract(): bool
    {
        $contractId = $this->input('id');

        return $contractId
            && Contract::query()->whereKey($contractId)->value('instrument_type') === 'lease_renewal';
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقد مطلوب.',
            'id.exists' => 'العقد المحدد غير موجود.',
            'tenant_entity.required' => 'نوع الكيان المستأجر مطلوب.',
            'tenant_entity.in' => 'الكيان المستأجر يجب أن يكون شخص أو مؤسسة.',
            'tenant_id_num.required_if' => 'رقم الهوية مطلوب إذا كان الكيان المستأجر شخصاً.',
            'tenant_dob_day.required_if' => 'يوم تاريخ ميلاد المستأجر مطلوب إذا كان الكيان شخصاً.',
            'tenant_dob_month.required_if' => 'شهر تاريخ ميلاد المستأجر مطلوب إذا كان الكيان شخصاً.',
            'tenant_dob_year.required_if' => 'سنة تاريخ ميلاد المستأجر مطلوبة إذا كان الكيان شخصاً.',
            'tenant_mobile.required_if' => 'رقم الجوال مطلوب إذا كان الكيان المستأجر شخصاً.',
            'tenant_mobile.regex' => 'رقم الجوال يجب أن يبدأ بـ 05 ويكون مكون من 10 أرقام.',
            'authorization_type.required_if' => 'نوع التوكيل مطلوب إذا كان الكيان مؤسسة.',
            'id_num_of_property_tenant_agent.min' => 'رقم الهوية لا يقل عن عشرة أرقام.',
            'dobof_property_tenant_agent.required_if' => 'تاريخ ميلاد وكيل المالك مطلوب.',
            'copy_of_the_owner_record.mimes' => 'نسخة السجل يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
        ];
    }
}

