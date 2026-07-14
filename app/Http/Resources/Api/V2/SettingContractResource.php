<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $instrumentType = (string) $this->instrument_type;

        return [
            'id' => $this->id,
            'instrument_type' => $instrumentType,
            'instrument_type_label' => Contract::instrumentTypeLabel($instrumentType),
            'realestate' => (bool) $this->realestate,
            'contract' => (bool) $this->contract,
            'label' => $this->label,
            'electricity_meter_fee_commercial_tenant' => $this->electricity_meter_fee_commercial_tenant !== null
                ? (float) $this->electricity_meter_fee_commercial_tenant
                : null,
            'electricity_meter_fee_housing_tenant' => $this->electricity_meter_fee_housing_tenant !== null
                ? (float) $this->electricity_meter_fee_housing_tenant
                : null,
            'water_meter_fee_commercial_tenant' => $this->water_meter_fee_commercial_tenant !== null
                ? (float) $this->water_meter_fee_commercial_tenant
                : null,
            'water_meter_fee_housing_tenant' => $this->water_meter_fee_housing_tenant !== null
                ? (float) $this->water_meter_fee_housing_tenant
                : null,
        ];
    }
}
