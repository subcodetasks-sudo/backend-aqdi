<?php

namespace App\Http\Resources\Api\V2\RealEstate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealEstateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
         
            'id' => $this->id,
            'uuid' => $this->uuid,
            'instrument_type' => $this->instrument_type,
            'created_at'=>$this->created_at,
            'property_type_id' => $this->property_type_id,
            'property_usages_id' => $this->property_usages_id,
             'number_of_floors' => $this->number_of_floors,
            'number_of_units_in_realestate' => $this->number_of_units_in_realestate,
            'image_instrument' => $this->image_instrument 
                ? asset('storage/' . $this->image_instrument) 
                : null,

            'image_address' => $this->image_address 
                ? asset('storage/' . $this->image_address) 
                : null,
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'step' => $this->step,
        ];
    }
}
