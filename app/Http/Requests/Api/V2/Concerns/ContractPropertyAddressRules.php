<?php

namespace App\Http\Requests\Api\V2\Concerns;

trait ContractPropertyAddressRules
{
    /**
     * @return array<string, mixed>
     */
    protected function contractPropertyAddressRules(bool $require = false): array
    {
        $presence = $require ? 'required' : 'nullable';

        return [
            'property_place_id' => [$presence, 'integer', 'exists:regions,id'],
            'property_city_id' => [$presence, 'integer', 'exists:cities,id'],
            'neighborhood' => [$presence, 'string', 'max:255'],
            'street' => [$presence, 'string', 'max:255'],
            'building_number' => [$presence, 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'extra_figure' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function contractPropertyAddressMessages(): array
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
        ];
    }
}
