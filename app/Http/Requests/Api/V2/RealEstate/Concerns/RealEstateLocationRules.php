<?php

namespace App\Http\Requests\Api\V2\RealEstate\Concerns;

trait RealEstateLocationRules
{
    /**
     * @return array<string, mixed>
     */
    protected function locationRules(bool $requireId = false): array
    {
        $rules = [
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

        if ($requireId) {
            $rules['id'] = 'required|exists:real_estates,id';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function locationMessages(): array
    {
        return [
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

    /**
     * @return array<string, mixed>
     */
    protected function locationAttributesForPayload(): array
    {
        return [
            'property_place_id' => $this->input('property_place_id'),
            'property_city_id' => $this->input('property_city_id'),
            'neighborhood' => $this->input('neighborhood'),
            'street' => $this->input('street'),
            'building_number' => $this->input('building_number'),
            'postal_code' => $this->input('postal_code'),
            'extra_figure' => $this->input('extra_figure'),
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
        ];
    }
}
