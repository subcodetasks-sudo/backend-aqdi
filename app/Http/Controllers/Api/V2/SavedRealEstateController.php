<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Contract;
use App\Models\RealEstate;
use App\Models\UnitsReal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SavedRealEstateController extends \App\Http\Controllers\Api\SavedRealEstateController
{
    public function SavedRealEstate(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'name_real_estate' => 'required|string',
        ]);

        $contract = Contract::findOrFail($validated['contract_id']);

        DB::beginTransaction();

        try {
            $real = RealEstate::create($this->realEstatePayloadFromContract($contract, $userId, $validated['name_real_estate']));

            $linkedUnits = $contract->units()->get();
            $primaryUnit = null;

            if ($linkedUnits->isNotEmpty()) {
                foreach ($linkedUnits as $linkedUnit) {
                    $linkedUnit->update([
                        'real_estates_units_id' => $real->id,
                    ]);
                }
                $primaryUnit = $linkedUnits->first();
            } else {
                $primaryUnit = UnitsReal::create(UnitsReal::attributesForApi(
                    $this->unitPayloadFromContract($contract, $real->id, $userId)
                ));

                \App\Models\ContractUnit::query()->create([
                    'contract_id' => $contract->id,
                    'real_unit_id' => $primaryUnit->id,
                    'real_estate_id' => $real->id,
                ]);
            }

            $contract->update([
                'real_id' => $real->id,
                'real_units_id' => $primaryUnit?->id,
                'is_real' => true,
            ]);

            // Refresh pivot real_estate_id
            \App\Models\ContractUnit::query()
                ->where('contract_id', $contract->id)
                ->update(['real_estate_id' => $real->id]);

            DB::commit();

            return response()->json([
                'message' => 'تمت إضافة العقار والوحدة بنجاح',
                'code' => Response::HTTP_CREATED,
                'success' => true,
                'data' => [
                    'real_estate' => $real->fresh(),
                    'units_real' => $primaryUnit?->fresh(),
                    'units' => $contract->units()->with(['unitType', 'unitUsage'])->get(),
                    'contract_v2_fields' => [
                        'image_instrument_from_the_front' => $contract->image_instrument_from_the_front,
                        'image_instrument_from_the_back' => $contract->image_instrument_from_the_back,
                        'Image_from_the_agency' => $contract->Image_from_the_agency,
                        'copy_power_of_attorney_from_heirs_to_agent' => $contract->copy_power_of_attorney_from_heirs_to_agent,
                        'Image_inheritance_certificate' => $contract->Image_inheritance_certificate,
                        'tenant_roles' => $contract->tenant_roles,
                        'tenant_role_ids' => $contract->tenant_role_ids ?? [],
                        'tenant_role_id' => $contract->tenant_role_id,
                        'additional_terms' => $contract->additional_terms,
                        'text_additional_terms' => $contract->text_additional_terms,
                    ],
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ أثناء إضافة العقار',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function realEstatePayloadFromContract(Contract $contract, int $userId, string $nameRealEstate): array
    {
        return [
            'user_id' => $userId,
            'property_owner_iban' => $contract->property_owner_iban,
            'contract_type' => $contract->contract_type,
            'date_first_registration' => $contract->date_first_registration,
            'real_estate_registry_number' => $contract->real_estate_registry_number,
            'name_real_estate' => $nameRealEstate,
            'name_owner' => $contract->name_owner,
            'number_of_units_in_realestate' => $contract->numberOfUnitsInRealestate(),
            'instrument_number' => $contract->instrument_number,
            'instrument_history' => $contract->instrument_history,
            'instrument_type' => $contract->instrument_type,
            'property_city_id' => $contract->property_city_id,
            'street' => $contract->street,
            'number_of_floors' => $contract->number_of_floors,
            'postal_code' => $contract->postal_code,
            'extra_figure' => $contract->extra_figure,
            'type_real_estate_other' => $contract->type_real_estate_other,
            'property_owner_id_num' => $contract->property_owner_id_num,
            'property_owner_dob_hijri' => $contract->property_owner_dob,
            'property_owner_mobile' => $contract->property_owner_mobile,
            'neighborhood' => $contract->neighborhood,
            'property_place_id' => $contract->property_place_id,
            'building_number' => $contract->building_number,
            'property_type_id' => $contract->property_type_id,
            'property_usages_id' => $contract->property_usages_id,
            'image_instrument' => $contract->image_instrument,
            'age_of_the_property' => $contract->age_of_the_property,
            'number_of_units_per_floor' => $contract->number_of_units_per_floor,
            'image_address' => $contract->image_address,
            'address_url' => $contract->address_url,
            'latitude' => $contract->latitude,
            'longitude' => $contract->longitude,
            'contract_ownership' => $contract->contract_ownership,
            'electricity_meter_ownership' => $contract->electricity_meter_ownership,
            'water_meter_ownership' => $contract->water_meter_ownership,
            'add_legal_agent_of_owner' => $contract->add_legal_agent_of_owner,
            'id_num_of_property_owner_agent' => $contract->id_num_of_property_owner_agent,
            'dob_of_property_owner_agent' => $contract->dob_of_property_owner_agent,
            'mobile_of_property_owner_agent' => $contract->mobile_of_property_owner_agent,
            'agency_number_in_instrument_of_property_owner' => $contract->agency_number_in_instrument_of_property_owner,
            'agency_instrument_date_of_property_owner' => $contract->agency_instrument_date_of_property_owner,
            'copy_of_the_authorization_or_agency' => $contract->copy_of_the_authorization_or_agency,
            'copy_of_the_endowment_registration_certificate' => $contract->copy_of_the_endowment_registration_certificate,
            'copy_of_the_trusteeship_deed' => $contract->copy_of_the_trusteeship_deed,
            'type_dob_property_owner' => $contract->type_dob_property_owner,
            'type_dob_property_owner_agent' => $contract->type_dob_property_owner_agent,
            'type_instrument_history' => $contract->type_instrument_history,
            'type_date_first_registration' => $contract->type_date_first_registration,
            'type_agency_instrument_date_of_property_owner' => $contract->type_agency_instrument_date_of_property_owner,
        ];
    }

    /**
     * Copy contract step-5 unit fields into a new real_units row.
     *
     * @return array<string, mixed>
     */
    private function unitPayloadFromContract(Contract $contract, int $realEstateId, int $userId): array
    {
        $attrs = $contract->getAttributes();

        return [
            'real_estates_units_id' => $realEstateId,
            'user_id' => $userId,
            'contract_type' => $contract->contract_type,
            'unit_number' => $contract->unit_number,
            'unit_type_id' => $contract->unit_type_id,
            'unit_usage_id' => $contract->unit_usage_id,
            'floor_number' => $contract->floor_number,
            'unit_area' => $contract->unit_area,
            'tootal_rooms' => $contract->tootal_rooms,
            'The_number_of_halls' => $contract->The_number_of_halls,
            'The_number_of_kitchens' => $contract->The_number_of_kitchens,
            'The_number_of_toilets' => $contract->The_number_of_toilets
                ?? ($attrs['The_number_of_the_toilet'] ?? null),
            'window_ac' => $contract->window_ac,
            'split_ac' => $contract->split_ac,
            'electricity_meter_number' => $contract->electricity_meter_number,
            'water_meter_number' => $contract->water_meter_number,
            'kitchen_tank' => (bool) $contract->kitchen_tank,
            'furnished' => (bool) $contract->furnished,
            'type_furnished' => \App\Support\TypeFurnished::normalize($contract->type_furnished),
            'electricity_meter' => (bool) $contract->electricity_meter,
            'water_meter' => (bool) $contract->water_meter,
            'electricity_meter_ownership' => $contract->electricity_meter_ownership,
            'water_meter_ownership' => $contract->water_meter_ownership,
            'Number_parking_spaces' => $attrs['Number_parking_spaces'] ?? null,
        ];
    }
}
