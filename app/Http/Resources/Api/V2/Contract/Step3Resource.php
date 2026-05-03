<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Support\DateInputNormalizer;
use App\Support\HijriDobParts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step3Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ownerDobStored = $this->property_owner_dob;
        $ownerDob = HijriDobParts::split(
            ($ownerDobStored !== null && $ownerDobStored !== '') ? $ownerDobStored : null
        );
        $agentDob = HijriDobParts::split($this->dob_hijri_of_property_owner_agent);
        $agencyDate = DateInputNormalizer::splitMysqlDate($this->agency_instrument_date_of_property_owner);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name_real_estate' => $this->name_real_estate,
            'name_owner' => $this->name_owner,
            'property_owner_id_num' => $this->property_owner_id_num,
            'property_owner_dob' => ($ownerDobStored !== null && $ownerDobStored !== '') ? $ownerDobStored : null,
            'property_owner_dob_day' => $ownerDob['day'],
            'type_dob_property_owner' => $this->type_dob_property_owner ?? $this->type_dob,
            'property_owner_dob_month' => $ownerDob['month'],
            'property_owner_dob_year' => $ownerDob['year'],
            'property_owner_mobile' => $this->property_owner_mobile,
            'property_owner_iban' => $this->property_owner_iban,
            'add_legal_agent_of_owner' => $this->add_legal_agent_of_owner,
            'id_num_of_property_owner_agent' => $this->id_num_of_property_owner_agent,
            'dob_of_property_owner_agent' => $this->dob_hijri_of_property_owner_agent,
            'type_dob_property_owner_agent' => $this->type_dob_property_owner_agent,
            'dob_of_property_owner_agent_day' => $agentDob['day'],
            'dob_of_property_owner_agent_month' => $agentDob['month'],
            'dob_of_property_owner_agent_year' => $agentDob['year'],
            'mobile_of_property_owner_agent' => $this->mobile_of_property_owner_agent,
            'agency_number_in_instrument_of_property_owner' => $this->agency_number_in_instrument_of_property_owner,
            'agency_instrument_date_of_property_owner' => $this->agency_instrument_date_of_property_owner,
            'type_agency_instrument_date_of_property_owner' => $this->type_agency_instrument_date_of_property_owner ?? 'hijri',
            'agency_instrument_date_of_property_owner_day' => $agencyDate['day'],
            'agency_instrument_date_of_property_owner_month' => $agencyDate['month'],
            'agency_instrument_date_of_property_owner_year' => $agencyDate['year'],
            'copy_of_the_authorization_or_agency' => $this->copy_of_the_authorization_or_agency
                ? asset('storage/' . $this->copy_of_the_authorization_or_agency)
                : null,
            'step' => $this->step,
        ];
    }
}
