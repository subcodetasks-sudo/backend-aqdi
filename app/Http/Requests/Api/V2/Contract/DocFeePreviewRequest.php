<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Support\DocFee;

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
            'duration_preset' => 'required|in:'.implode(',', DocFee::presetKeys()),
            'duration_years' => 'nullable|integer|min:1|max:30|required_if:duration_preset,other',
            'duration_months' => 'nullable|integer|min:0|max:11|required_if:duration_preset,other',
        ];
    }

    public function messages(): array
    {
        return [
            'contract_type.required' => 'نوع العقد مطلوب.',
            'contract_type.in' => 'نوع العقد يجب أن يكون سكني أو تجاري.',
            'duration_preset.required' => 'مدة العقد مطلوبة.',
            'duration_preset.in' => 'مدة العقد غير صالحة.',
            'duration_years.required_if' => 'عدد السنوات مطلوب عند اختيار مدة أخرى.',
            'duration_years.min' => 'عدد السنوات يجب ألا يقل عن 1.',
            'duration_years.max' => 'عدد السنوات يجب ألا يزيد عن 30.',
            'duration_months.required_if' => 'عدد الأشهر مطلوب عند اختيار مدة أخرى.',
            'duration_months.min' => 'عدد الأشهر يجب ألا يقل عن 0.',
            'duration_months.max' => 'عدد الأشهر يجب ألا يزيد عن 11.',
        ];
    }
}
