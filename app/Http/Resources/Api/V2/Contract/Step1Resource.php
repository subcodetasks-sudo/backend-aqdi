<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step1Resource extends JsonResource
{
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type,
            'contract_type_trans' => $this->contract_type_trans,
            'real_id' => $this->real_id,
            'real_units_id' => $this->real_units_id,
            'instrument_type' => $this->instrument_type,
            'instrument_type_trans' => $this->instrument_type_trans,
            'number_of_units_in_realestate' => $this->number_of_units_in_realestate,
            'number_of_floors' => $this->number_of_floors,
            'property_type_id' => $this->property_type_id,
            'property_usages_id' => $this->property_usages_id,
            'image_instrument' => $this->image_instrument ? asset('storage/'.$this->image_instrument) : null,
            'image_instrument_from_the_front' => $this->image_instrument_from_the_front ? asset('storage/'.$this->image_instrument_from_the_front) : null,
            'image_instrument_from_the_back' => $this->image_instrument_from_the_back ? asset('storage/'.$this->image_instrument_from_the_back) : null,
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'instrument_number' => $this->instrument_number,
            'instrument_history' => $this->instrument_history,
            'type_instrument_history' => $this->type_instrument_history ?? 'hijri',
            'real_estate_registry_number' => $this->real_estate_registry_number,
            'date_first_registration' => $this->date_first_registration,
            'type_date_first_registration' => $this->type_date_first_registration ?? 'hijri',
            'copy_of_the_endowment_registration_certificate' => $this->copy_of_the_endowment_registration_certificate
                ? asset('storage/'.$this->copy_of_the_endowment_registration_certificate)
                : null,
            'copy_of_the_trusteeship_deed' => $this->copy_of_the_trusteeship_deed
                ? asset('storage/'.$this->copy_of_the_trusteeship_deed)
                : null,
            'is_multiple_trusteeship_deed_copy' => (bool) $this->is_multiple_trusteeship_deed_copy,
            'step' => $this->step,

        ];
    }
}

