<?php

namespace App\Http\Resources\Api\V2\RealEstate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step1RealEstateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_ownership' => $this->contract_ownership,
            'contract_type' => $this->contract_type,
            'instrument_type' => $this->instrument_type,
            'property_type_id' => $this->property_type_id,
            'property_type_name' => optional($this->propertyType)->name_trans
                ?? optional($this->propertyType)->name_ar,
            'property_usages_id' => $this->property_usages_id,
            'property_usages_name' => optional($this->propertyUsages)->name_trans
                ?? optional($this->propertyUsages)->name_ar,
            'number_of_floors' => $this->number_of_floors,
            'number_of_units_in_realestate' => $this->number_of_units_in_realestate,
            'property_owner_is_deceased' => $this->property_owner_is_deceased,
            'instrument_number' => $this->instrument_number,
            'instrument_history' => $this->instrument_history,
            'type_instrument_history' => $this->type_instrument_history ?? 'hijri',
            'real_estate_registry_number' => $this->real_estate_registry_number,
            'date_first_registration' => $this->date_first_registration,
            'type_date_first_registration' => $this->type_date_first_registration ?? 'hijri',
            'name_real_estate' => $this->name_real_estate,
            'image_instrument' => $this->image_instrument
                ? asset('storage/'.$this->image_instrument)
                : null,
            'copy_of_the_endowment_registration_certificate' => $this->copy_of_the_endowment_registration_certificate
                ? asset('storage/'.$this->copy_of_the_endowment_registration_certificate)
                : null,
            'copy_of_the_trusteeship_deed' => $this->copy_of_the_trusteeship_deed
                ? asset('storage/'.$this->copy_of_the_trusteeship_deed)
                : null,
            'is_multiple_trusteeship_deed_copy' => (bool) $this->is_multiple_trusteeship_deed_copy,
            'copy_of_guardians_power_of_attorney_for_agent' => $this->copy_of_guardians_power_of_attorney_for_agent
                ? asset('storage/'.$this->copy_of_guardians_power_of_attorney_for_agent)
                : null,
            'image_address' => $this->image_address
                ? asset('storage/'.$this->image_address)
                : null,
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'property_place_id' => $this->property_place_id,
            'property_city_id' => $this->property_city_id,
            'property_place_name' => optional($this->tenantEntityRegion)->name_trans
                ?? optional($this->tenantEntityRegion)->name_ar,
            'property_city_name' => optional($this->tenantEntityCity)->name_trans
                ?? optional($this->tenantEntityCity)->name_ar,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'postal_code' => $this->postal_code,
            'extra_figure' => $this->extra_figure,
            'latitude' => $lat = $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $lng = $this->longitude !== null ? (float) $this->longitude : null,
            'lat' => $lat,
            'lng' => $lng,
            'step' => $this->step,
        ];
    }
}
