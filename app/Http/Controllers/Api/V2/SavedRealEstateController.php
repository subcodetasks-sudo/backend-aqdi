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
            $realData = [
                'user_id' => $userId,
                'property_owner_iban' => $contract->property_owner_iban,
                'contract_type' => $contract->contract_type,
                'date_first_registration' => $contract->date_first_registration,
                'real_estate_registry_number' => $contract->real_estate_registry_number,
                'name_real_estate' => $validated['name_real_estate'],
                'name_owner' => $contract->name_owner,
                'number_of_units_in_realestate' => $contract->numberOfUnitsInRealestate(),
                'unit_number' => $contract->unit_number,
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
                'property_owner_dob' => $contract->property_owner_dob,
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
                'latitude' => $contract->latitude,
                'longitude' => $contract->longitude,
            ];

            $real = RealEstate::create($realData);

            $unitData = [
                'real_estates_units_id' => $real->id,
                'user_id' => $userId,
                'unit_number' => $contract->unit_number,
                'unit_area' => $contract->unit_area,
                'electricity_meter_number' => $contract->electricity_meter_number,
                'water_meter_number' => $contract->water_meter_number,
                'Gasmeter' => $contract->Gasmeter,
                'window_ac' => $contract->window_ac,
                'split_ac' => $contract->split_ac,
                'tootal_rooms' => $contract->tootal_rooms,
                'The_number_of_toilets' => $contract->The_number_of_toilets,
                'number_of_kitchens' => $contract->number_of_kitchens,
                'unit_usage_id' => $contract->unit_usage_id,
                'unit_type_id' => $contract->unit_type_id,
                'floor_number' => $contract->floor_number,
                'number_of_parking_spaces' => $contract->number_of_parking_spaces,
                'number_of_halls' => $contract->number_of_halls,
            ];

            $unit = UnitsReal::create($unitData);

            DB::commit();

            return response()->json([
                'message' => 'تمت إضافة العقار بنجاح',
                'code' => Response::HTTP_CREATED,
                'success' => true,
                'data' => [
                    'real_estate' => $real,
                    'units_real' => $unit,
                    // Expose contract V2 fields so the client has all newly added data.
                    'contract_v2_fields' => [
                        'image_instrument_from_the_front' => $contract->image_instrument_from_the_front,
                        'image_instrument_from_the_back' => $contract->image_instrument_from_the_back,
                        'Image_from_the_agency' => $contract->Image_from_the_agency,
                        'copy_power_of_attorney_from_heirs_to_agent' => $contract->copy_power_of_attorney_from_heirs_to_agent,
                        'Image_inheritance_certificate' => $contract->Image_inheritance_certificate,
                        'tenant_roles' => $contract->tenant_roles,
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
}

