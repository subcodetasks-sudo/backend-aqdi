<?php

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'real_estates_units_id' => $this->real_estates_units_id,
            'unit_number' => $this->unit_number,
            'unit_type_id' => $this->unit_type_id,
            'unit_type_name' => optional($this->unitType)->name_ar,
            'contract_type' => optional($this->unitType)->contract_type,
            'unit_usage_id' => $this->unit_usage_id,
            'unit_usage_name' => optional($this->unitUsage)->name_ar,
            'floor_number' => $this->floor_number,
            'unit_area' => $this->unit_area,
            'tootal_rooms' => $this->tootal_rooms,
            'The_number_of_halls' => $this->The_number_of_halls,
            'The_number_of_kitchens' => $this->The_number_of_kitchens,
            'The_number_of_toilets' => $this->The_number_of_toilets,
            'window_ac' => $this->window_ac,
            'split_ac' => $this->split_ac,
            'electricity_meter_number' => $this->electricity_meter_number,
            'water_meter_number' => $this->water_meter_number,
            'kitchen_tank' => (bool) $this->kitchen_tank,
            'furnished' => (bool) $this->furnished,
            'type_furnished' => (bool) $this->type_furnished,
            'electricity_meter' => (bool) $this->electricity_meter,
            'water_meter' => (bool) $this->water_meter,
        ];
    }
}

