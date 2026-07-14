<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;

class DocFeePreviewRequest extends BaseApiV2Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_type' => 'required|in:housing,commercial',
            'duration_preset' => 'required|in:other',
            'duration_years' => 'required|integer|min:1|max:30',
            'duration_months' => 'required|integer|min:0|max:11',
        ];
    }

    public function messages(): array
    {
        return [
            'contract_type.required' => 'نوع العقد مطلوب.',
            'contract_type.in' => 'نوع العقد يجب أن يكون سكني أو تجاري.',
            'duration_preset.required' => 'مدة أخرى مطلوبة.',
            'duration_preset.in' => 'قيمة مدة أخرى غير صالحة.',
            'duration_years.required' => 'عدد السنوات مطلوب.',
            'duration_years.min' => 'عدد السنوات يجب ألا يقل عن 1.',
            'duration_years.max' => 'عدد السنوات يجب ألا يزيد عن 30.',
            'duration_months.required' => 'عدد الأشهر مطلوب.',
            'duration_months.min' => 'عدد الأشهر يجب ألا يقل عن 0.',
            'duration_months.max' => 'عدد الأشهر يجب ألا يزيد عن 11.',
        ];
    }
}
