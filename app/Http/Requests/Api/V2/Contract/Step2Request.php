<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Models\Contract;
use Illuminate\Validation\Rule;

class Step2Request extends BaseApiV2Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skipStepTwo = $this->shouldSkipStepTwo();

        return [
            'id' => 'required|exists:contracts,id',
            'property_place_id' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'integer', 'exists:regions,id'],
            'property_city_id' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'integer', 'exists:cities,id'],
            'neighborhood' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            'street' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            'building_number' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:50'],
            'postal_code' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:20'],
            'extra_figure' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            'image_address' => 'nullable|image',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }

    private function shouldSkipStepTwo(): bool
    {
        $contractId = $this->input('id');
        if (! $contractId) {
            return false;
        }

        $instrumentType = Contract::query()->whereKey($contractId)->value('instrument_type');

        return Contract::shouldSkipInitialSteps($instrumentType);
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقد مطلوب.',
            'id.exists' => 'العقد المحدد غير موجود.',
            'property_place_id.required' => 'المنطقة مطلوبة.',
            'property_place_id.exists' => 'المنطقة المحددة غير موجودة.',
            'property_city_id.required' => 'المدينة مطلوبة.',
            'property_city_id.exists' => 'المدينة المحددة غير موجودة.',
            'neighborhood.required' => 'الحي مطلوب.',
            'street.required' => 'الشارع مطلوب.',
            'building_number.required' => 'رقم المبنى مطلوب.',
            'postal_code.required' => 'الرمز البريدي مطلوب.',
            'extra_figure.required' => 'الرقم الإضافي مطلوب.',
            'image_address.image' => 'صورة العنوان يجب أن تكون ملف صورة.',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقماً.',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقماً.',
        ];
    }
}

