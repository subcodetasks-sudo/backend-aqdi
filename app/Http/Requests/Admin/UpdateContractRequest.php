<?php

namespace App\Http\Requests\Admin;

use App\Models\RealEstate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['id', 'uuid', 'created_at', 'updated_at'] as $key) {
            $this->request->remove($key);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $instrumentTypes = array_values(array_unique(RealEstate::INSTRUMENT_TYPES));

        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'contract_type' => ['sometimes', 'nullable', Rule::in(['housing', 'commercial'])],
            'contract_ownership' => ['sometimes', 'nullable', Rule::in(['owner', 'tenant'])],
            'instrument_type' => ['sometimes', 'nullable', Rule::in($instrumentTypes)],
            'status' => ['sometimes', 'nullable', Rule::in(['retrieved', 'cancel'])],
            'instrument_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'instrument_history' => ['sometimes', 'nullable', 'date'],
            'date_first_registration' => ['sometimes', 'nullable', 'string', 'max:255'],
            'real_estate_registry_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_units_in_realestate' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_units_per_floor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'property_owner_is_deceased' => ['sometimes', 'nullable', 'boolean'],
            'property_usages_id' => ['sometimes', 'nullable', 'integer', 'exists:rea_estat_usages,id'],
            'property_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'property_place_id' => ['sometimes', 'nullable', 'integer', 'exists:regions,id'],
            'property_type_id' => ['sometimes', 'nullable', 'integer', 'exists:rea_estat_types,id'],
            'neighborhood' => ['sometimes', 'nullable', 'string', 'max:255'],
            'building_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'extra_figure' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_floors' => ['sometimes', 'nullable', 'string', 'max:255'],
            'street' => ['sometimes', 'nullable', 'string', 'max:500'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'age_of_the_property' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'property_owner_id_num' => ['sometimes', 'nullable', 'string', 'max:255'],
            'property_owner_dob' => ['sometimes', 'nullable', 'string', 'max:255'],
            'property_owner_mobile' => ['sometimes', 'nullable', 'string', 'max:255'],
            'property_owner_iban' => ['sometimes', 'nullable', 'string', 'max:255'],
            'add_legal_agent_of_owner' => ['sometimes', 'nullable', 'boolean'],
            'id_num_of_property_owner_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dob_gregorian_of_property_owner_agent' => ['sometimes', 'nullable', 'date'],
            'dob_hijri_of_property_owner_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile_of_property_owner_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'agency_number_in_instrument_of_property_owner' => ['sometimes', 'nullable', 'string', 'max:255'],
            'agency_instrument_date_of_property_owner' => ['sometimes', 'nullable', 'date'],
            'agent_iban_of_property_owner' => ['sometimes', 'nullable', 'string', 'max:255'],

            'tenant_id_num' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tenant_dob_gregorian' => ['sometimes', 'nullable', 'date'],
            'tenant_dob_hijri' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tenant_mobile' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_owner' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_real_estate' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type_real_estate_other' => ['sometimes', 'nullable', 'string', 'max:255'],

            'add_legal_agent_of_tenant' => ['sometimes', 'nullable', 'boolean'],
            'id_num_of_property_tenant_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dob_gregorian_of_property_tenant_agent' => ['sometimes', 'nullable', 'date'],
            'dob_hijri_of_property_tenant_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile_of_property_tenant_agent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'agency_number_in_instrument_of_property_tenant' => ['sometimes', 'nullable', 'string', 'max:255'],
            'agency_instrument_date_of_property_tenant' => ['sometimes', 'nullable', 'date'],

            'tenant_entity' => ['sometimes', 'nullable', Rule::in(['person', 'institution'])],
            'tenant_entity_unified_registry_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tenant_entity_region_id' => ['sometimes', 'nullable', 'integer', 'exists:regions,id'],
            'tenant_entity_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'authorization_type' => ['sometimes', 'nullable', Rule::in(['owner_and_representative_of_record', 'agent_for_the_tenant'])],
            'copy_of_the_authorization_or_agency' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'copy_of_the_owner_record' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'city_of_the_tenant_legal_agent' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'region_of_the_tenant_legal_agent' => ['sometimes', 'nullable', 'integer', 'exists:regions,id'],

            'unit_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_type_id' => ['sometimes', 'nullable', 'integer', 'exists:unit_types,id'],
            'unit_usage_id' => ['sometimes', 'nullable', 'integer', 'exists:unit_usages,id'],
            'tootal_rooms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'floor_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'electricity_meter_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'water_meter_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_unit_air_conditioners' => ['sometimes', 'nullable', 'string', 'max:255'],
            'window_ac' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'split_ac' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'kitchen_tank' => ['sometimes', 'nullable', 'boolean'],
            'furnished' => ['sometimes', 'nullable', 'boolean'],
            'type_furnished' => ['sometimes', 'nullable', 'boolean'],
            'electricity_meter' => ['sometimes', 'nullable', 'boolean'],
            'water_meter' => ['sometimes', 'nullable', 'boolean'],
            'The_number_of_halls' => ['sometimes', 'nullable', 'string', 'max:255'],
            'The_number_of_kitchens' => ['sometimes', 'nullable', 'string', 'max:255'],
            'The_number_of_toilets' => ['sometimes', 'nullable', 'string', 'max:255'],
            'The_number_of_the_toilet' => ['sometimes', 'nullable', 'string', 'max:255'],
            'Gasmeter' => ['sometimes', 'nullable', 'string', 'max:255'],
            'Number_parking_spaces' => ['sometimes', 'nullable', 'string', 'max:255'],

            'contract_starting_date' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contract_term_in_years' => ['sometimes', 'nullable', 'integer', 'exists:contract_periods,id'],
            'annual_rent_amount_for_the_unit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payment_type_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_types,id'],
            'daily_fine' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sub_delay' => ['sometimes', 'nullable', 'boolean'],
            'other_conditions' => ['sometimes', 'nullable', 'string'],
            'premium_membership_for_free' => ['sometimes', 'nullable', 'boolean'],
            'deposit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'Guarantee_amount' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contract_period_id' => ['sometimes', 'nullable', 'integer', 'exists:contract_periods,id'],
            'real_id' => ['sometimes', 'nullable', 'integer', 'exists:real_estates,id'],
            'real_units_id' => ['sometimes', 'nullable', 'integer', 'exists:real_units,id'],
            'additional_terms' => ['sometimes', 'nullable', 'boolean'],
            'text_additional_terms' => ['sometimes', 'nullable', 'string'],
            'tenant_roles' => ['sometimes', 'nullable', 'boolean'],
            'tenant_role_id' => ['sometimes', 'nullable', 'integer', 'exists:tenant_roles,id'],

            'client_account_holder_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'draft_before_paid' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'draft_after_paid' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'bank_account_number' => ['sometimes', 'nullable', 'string', 'max:255'],

            'image_instrument' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'image_address' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'image_instrument_from_the_front' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'image_instrument_from_the_back' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'Image_from_the_agency' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'copy_power_of_attorney_from_heirs_to_agent' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'Image_inheritance_certificate' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'strong_argument_photo' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'photo_of_the_electronic' => ['sometimes', 'nullable', 'string', 'max:2048'],

            'rating' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:5'],
            'rating_note' => ['sometimes', 'nullable', 'string'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'Services' => ['sometimes', 'nullable', 'boolean'],
            'step' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20'],
            'is_completed' => ['sometimes', 'nullable', 'boolean'],
            'is_delete' => ['sometimes', 'nullable', 'boolean'],
            'is_real' => ['sometimes', 'nullable', 'boolean'],
            'is_review' => ['sometimes', 'nullable', 'boolean'],
            'file' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'app_or_web' => ['sometimes', 'nullable', Rule::in(['app', 'web'])],
            'contract_status_id' => ['sometimes', 'nullable', 'integer', 'exists:contract_statuses,id'],
        ];
    }
}
