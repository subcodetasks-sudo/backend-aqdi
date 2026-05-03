<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step5Resource extends JsonResource
{
    use WithContractDocumentationDeadline;

    private function asBool(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'uuid' => $this->uuid,
            'unit_type_id' => $this->unit_type_id,
            'unit_usage_id' => $this->unit_usage_id,
            'unit_number' => $this->unit_number,
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
            'kitchen_tank' => $this->asBool($this->kitchen_tank),
            'furnished' => $this->asBool($this->furnished),
            'type_furnished' => $this->asBool($this->type_furnished),
            'electricity_meter' => $this->asBool($this->electricity_meter),
            'water_meter' => $this->asBool($this->water_meter),
            'step' => $this->step,
        ];
    }
}

