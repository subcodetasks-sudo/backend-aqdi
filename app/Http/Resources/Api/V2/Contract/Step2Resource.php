<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step2Resource extends JsonResource
{
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        return [
         
            
            'uuid' => $this->uuid,
               'id' => $this->id,
            'contract_ownership' => $this->contract_ownership,
            'contract_type' => $this->contract_type,
            'instrument_type' => $this->instrument_type,
            'property_type_id' => $this->property_type_id,
            'property_usages_id' => $this->property_usages_id,
            'number_of_floors' => $this->number_of_floors,
            'number_of_units_in_realestate' => $this->number_of_units_in_realestate,
            'property_owner_is_deceased' => $this->property_owner_is_deceased,
           
            //'real_estate_registry_number' => $this->real_estate_registry_number,
           // 'date_first_registration' => $this->date_first_registration,
           // 'name_real_estate' => $this->name_real_estate,
            'image_instrument' => $this->image_instrument
                ? asset('storage/'.$this->image_instrument)
                : null,
            'image_address' => $this->image_address
                ? asset('storage/'.$this->image_address)
                : null,
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'step' => $this->step,
        ];
    }
}

