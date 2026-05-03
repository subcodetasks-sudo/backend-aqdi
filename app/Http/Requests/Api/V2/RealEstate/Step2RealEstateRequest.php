<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;

/**
 * V2 real-estate step 2: same as Contract Step2Request; id is real_estates.id.
 */
class Step2RealEstateRequest extends BaseApiV2Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:real_estates,id',
            'property_place_id' => 'required|integer|exists:regions,id',
            'property_city_id' => 'required|integer|exists:cities,id',
            'neighborhood' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'building_number' => 'required|string|max:50',
            'postal_code' => 'required|string|max:20',
            'extra_figure' => 'required|string|max:255',
            'image_address' => 'nullable|image',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقار مطلوب.',
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
