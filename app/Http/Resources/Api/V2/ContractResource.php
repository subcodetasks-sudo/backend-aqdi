<?php

namespace App\Http\Resources\Api\V2;

use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        return $this->withDocumentationDeadline([
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type,
            'contract_ownership' => $this->contract_ownership,
            'name_real_estate' => $this->name_real_estate,
            'property_owner_id_num' => $this->property_owner_id_num,
            'tenant_id_num' => $this->tenant_id_num,
            'instrument_type' => $this->instrument_type,
            'image_instrument' => $this->image_instrument,
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'image_address' => $this->image_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'image_instrument_from_the_front' => $this->image_instrument_from_the_front,
            'image_instrument_from_the_back' => $this->image_instrument_from_the_back,
            'Image_from_the_agency' => $this->Image_from_the_agency,
            'copy_power_of_attorney_from_heirs_to_agent' => $this->copy_power_of_attorney_from_heirs_to_agent,
            'Image_inheritance_certificate' => $this->Image_inheritance_certificate,
            'tenant_roles' => (bool) $this->tenant_roles,
            'tenant_role_id' => $this->tenant_role_id,
            'additional_terms' => (bool) $this->additional_terms,
            'text_additional_terms' => $this->text_additional_terms,
            'is_completed' => (bool) $this->is_completed,
            'step' => $this->step,
            'contract_status_id' => $this->contract_status_id,
            'contract_status_name' => optional($this->contractStatus)->name
                ? trans(optional($this->contractStatus)->name)
                : null,
            'number_of_units_in_realestate' => $this->numberOfUnitsInRealestate(),
            'created_at' => optional($this->created_at)->format('Y-m-d'),
        ]);
    }
}

