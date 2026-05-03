<?php

namespace App\Http\Resources\Api\V2\RealEstate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step2RealEstateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_place_id' => $this->property_place_id,
            'property_city_id' => $this->property_city_id,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'postal_code' => $this->postal_code,
            'extra_figure' => $this->extra_figure,
            'image_address' => $this->image_address
                ? asset('storage/'.$this->image_address)
                : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'step' => $this->step,

            
        ];
    }
}
