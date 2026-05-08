<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step1Resource extends JsonResource
{
    use WithContractDocumentationDeadline;

    private function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        return getFilePath($normalized);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->id,
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
            'image_instrument' => $this->fileUrl($this->image_instrument),
            'image_instrument_from_the_front' => $this->fileUrl($this->image_instrument_from_the_front),
            'image_instrument_from_the_back' => $this->fileUrl($this->image_instrument_from_the_back),
            'age_of_the_property' => $this->age_of_the_property,
            'number_of_units_per_floor' => $this->number_of_units_per_floor,
            'instrument_number' => $this->instrument_number,
            'instrument_history' => $this->instrument_history,
            'type_instrument_history' => $this->type_instrument_history ?? 'hijri',
            'real_estate_registry_number' => $this->real_estate_registry_number,
            'date_first_registration' => $this->date_first_registration,
            'type_date_first_registration' => $this->type_date_first_registration ?? 'hijri',
            'copy_of_the_endowment_registration_certificate' => $this->fileUrl($this->copy_of_the_endowment_registration_certificate),
            'copy_of_the_trusteeship_deed' => $this->fileUrl($this->copy_of_the_trusteeship_deed),
            'is_multiple_trusteeship_deed_copy' => (bool) $this->is_multiple_trusteeship_deed_copy,
            'step' => $this->step,

        ];
    }
}

