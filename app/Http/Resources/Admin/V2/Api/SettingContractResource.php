<?php

namespace App\Http\Resources\Admin\V2\Api;

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
            'sms_user' => $this->sms_user,
            'sms_owner' => $this->sms_owner,
            'sms_employee' => $this->sms_employee,
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
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
