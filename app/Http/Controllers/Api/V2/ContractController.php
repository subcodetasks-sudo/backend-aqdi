<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Contract\ContractTypeRequest;
use App\Http\Requests\Api\V2\Contract\Step1Request;
use App\Http\Requests\Api\V2\Contract\Step2Request;
use App\Http\Requests\Api\V2\Contract\Step3Request;
use App\Http\Requests\Api\V2\Contract\Step4Request;
use App\Http\Requests\Api\V2\Contract\Step5Request;
use App\Http\Requests\Api\V2\Contract\Step6Request;
use App\Http\Resources\Api\V2\Contract\Step1Resource;
use App\Http\Resources\Api\V2\Contract\Step2Resource;
use App\Http\Resources\Api\V2\Contract\Step3Resource;
use App\Http\Resources\Api\V2\Contract\Step4Resource;
use App\Http\Resources\Api\V2\Contract\Step5Resource;
use App\Http\Resources\Api\V2\ContractResource;
use App\Http\Traits\Responser;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\RealEstate;
use App\Models\ServicesPricing;
use App\Models\Setting;
use App\Support\ContractStartingDateInput;
use App\Support\DateInputNormalizer;
use App\Support\HijriDobParts;

class ContractController extends Controller
{
    use Responser;
    public function index()
    {
        $user = auth()->user();
        $contracts = Contract::where('user_id', $user->id)
            ->with(['realEstate', 'contractStatus'])
            ->orderBy('created_at', 'desc')
            ->where('step', '>', '6')
            ->where('is_delete', 0)
            ->paginate(10);

        return $this->apiResponse(
            [
                'data' => ContractResource::collection($contracts),
                
                'pagination' => $this->paginate($contracts),
            ],
            trans('api.success')
        );
    }

    public function show($id)
    {
        $user = auth()->user();
        $contract = Contract::with(['realEstate', 'contractStatus'])->where('user_id', $user->id)->findOrFail($id);

        return $this->apiResponse(new ContractResource($contract), trans('api.success'));
    }

    public function start(ContractTypeRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $contract = Contract::create([
            'contract_type' => $validated['contract_type'],
            'instrument_type' => $validated['instrument_type'] ?? null,
            'is_real' => (bool) ($validated['is_real'] ?? false),
            'real_id' => $validated['real_id'] ?? null,
            'real_units_id' => $validated['real_units_id'] ?? null,
            'user_id' => $user->id,
            'step' => Contract::shouldSkipInitialSteps($validated['instrument_type'] ?? null) ? 3 : 1,
        ]);

        return $this->apiResponse(
            [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
            trans('api.success')
        );
    }
     

    public function step1(Step1Request $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        $contract = Contract::where('user_id', $user->id)->findOrFail($validated['id']);

        $step1Data = [
            'instrument_type' => $validated['instrument_type'] ?? null,
            'app_or_web' => 'app',
            'image_instrument_from_the_back'=>$validated['image_instrument_from_the_back']??null,
            'image_instrument_from_the_front'=>$validated['image_instrument_from_the_front']??null,
            'property_type_id' => $validated['property_type_id'] ?? null,
            'property_usages_id' => $validated['property_usages_id'] ?? null,

            'age_of_the_property' => $validated['age_of_the_property'] ?? null,
            'number_of_floors' => $validated['number_of_floors'] ?? null,
            'number_of_units_per_floor' => $validated['number_of_units_per_floor'] ?? null,
            'number_of_units_in_realestate' => $validated['number_of_units_in_realestate'] ?? null,
            'is_multiple_trusteeship_deed_copy' => array_key_exists('is_multiple_trusteeship_deed_copy', $validated)
                ? (bool) $validated['is_multiple_trusteeship_deed_copy']
                : (bool) $contract->is_multiple_trusteeship_deed_copy,
            'step' => Contract::shouldSkipInitialSteps($validated['instrument_type'] ?? $contract->instrument_type) ? 3 : 2,
        ];

        $contract->update($step1Data);

        if ($contract->real_id) {
            $contract->loadMissing('realEstate');
            $fromReal = $contract->realEstate?->number_of_units_in_realestate;
            if ($fromReal !== null && $fromReal !== '') {
                $contract->update([
                    'number_of_units_in_realestate' => $fromReal,
                ]);
            }
        }

        $imageInstrumentFile = $request->file('image_instrument');
        if ($imageInstrumentFile instanceof \Illuminate\Http\UploadedFile && $imageInstrumentFile->isValid()) {
            $contract->update([
                'image_instrument' => $imageInstrumentFile->store('images/contracts', 'public'),
            ]);
        } elseif (array_key_exists('image_instrument', $validated) && is_string($validated['image_instrument']) && $validated['image_instrument'] !== '') {
            $contract->update([
                'image_instrument' => $validated['image_instrument'],
            ]);
        }

        if ($request->hasFile('copy_of_the_endowment_registration_certificate')) {
            $contract->update([
                'copy_of_the_endowment_registration_certificate' => $request->file('copy_of_the_endowment_registration_certificate')
                    ->store('contracts/endowment-registration-certificates', 'public'),
            ]);
        }

        if ($request->hasFile('copy_of_the_trusteeship_deed')) {
            $contract->update([
                'copy_of_the_trusteeship_deed' => $request->file('copy_of_the_trusteeship_deed')
                    ->store('contracts/trusteeship-deeds', 'public'),
            ]);
        }

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step1Resource($contract->fresh(['realEstate'])),
        ], 200);
    }
 
   

    public function step2(Step2Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        if (Contract::shouldSkipInitialSteps($contract->instrument_type)) {
            $contract->update(['step' => 3]);

            return response()->json([
                'message' => trans('api.success'),
                'code' => 200,
                'success' => true,
                'data' => new Step2Resource($contract->fresh()),
            ]);
        }

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $city = City::where('id', $request->property_city_id)
            ->where('region_id', $request->property_place_id)
            ->first();

        if (! $city) {
            return $this->errorMessage(trans('api.city_not_include_region'));
        }

        $data = [
            'property_place_id' => $request->property_place_id,
            'property_city_id' => $request->property_city_id,
            'neighborhood' => $request->neighborhood,
            'street' => $request->street,
            'building_number' => $request->building_number,
            'image_address'=>$request->image_address,
            'postal_code' => $request->postal_code,
            'extra_figure' => $request->extra_figure,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'step' => 3,
        ];

        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/contracts', 'public');
        }

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step2Resource($contract->fresh()),
        ]);
    }

    public function step3(Step3Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $data = $this->buildStep3BaseData($request, $contract);

        $shouldApplyAgentBlock = $contract->instrument_type !== 'lease_renewal'
            || $request->has('add_legal_agent_of_owner');

        if ($shouldApplyAgentBlock) {
            $data = $this->hasOwnerAgent($request)
                ? $this->appendStep3AgentData($data, $request, $contract)
                : $this->appendStep3NoAgentData($data);
        }

        $contract->update($data);
        $this->syncStep3RealEstateName($contract, $request);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step3Resource($contract->fresh()),
        ]);
    }

    private function buildStep3BaseData(Step3Request $request, Contract $contract): array
    {
        $dob = $request->resolvedPropertyOwnerDobString();
        $ownerDobTypeNorm = $this->normalizeOwnerCalendarType(
            $request->input('type_dob_property_owner', $request->input('type_dob'))
        );

        $typePayload = [
            'type_dob_property_owner' => $ownerDobTypeNorm,
            'type_dob' => $ownerDobTypeNorm,
        ];

        $dobPayload = [
            'property_owner_dob' => $dob,
        ];

        if ($contract->instrument_type === 'lease_renewal') {
            $data = array_merge([
                'step' => 5,
            ], $typePayload, $dobPayload);
            if ($request->filled('name_owner')) {
                $data['name_owner'] = $request->name_owner;
            }
            if ($request->filled('property_owner_id_num')) {
                $data['property_owner_id_num'] = $request->property_owner_id_num;
            }
            if ($request->filled('property_owner_mobile')) {
                $data['property_owner_mobile'] = $request->property_owner_mobile;
            }
            if ($request->has('property_owner_iban')) {
                $data['property_owner_iban'] = $request->property_owner_iban;
            }
            if ($request->has('add_legal_agent_of_owner')) {
                $data['add_legal_agent_of_owner'] = $request->input('add_legal_agent_of_owner');
            }

            return $data;
        }

        return array_merge([
            'name_owner' => $request->name_owner,
            'property_owner_id_num' => $request->property_owner_id_num,
            'property_owner_mobile' => $request->property_owner_mobile,
            'property_owner_iban' => $request->property_owner_iban,
            'add_legal_agent_of_owner' => $request->add_legal_agent_of_owner,
            'step' => 4,
        ], $typePayload, $dobPayload);
    }

    private function normalizeOwnerCalendarType(mixed $value): string
    {
        $raw = strtolower(trim((string) ($value ?? 'hijri')));

        return in_array($raw, ['hijri', 'gregorian'], true) ? $raw : 'hijri';
    }

    /**
     * Step 6: `tenant_role_ids` is the canonical list; `tenant_role_id` keeps the first id for legacy BelongsTo.
     *
     * @return array{0: list<int>, 1: int|null}
     */
    private function normalizeTenantRoleIdsFromStep6Request(Step6Request $request): array
    {
        $raw = $request->input('tenant_role_ids');
        $ids = is_array($raw) ? $raw : [];

        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids))));

        $first = $ids[0] ?? null;

        return [$ids, $first];
    }

    private function hasOwnerAgent(Step3Request $request): bool
    {
        $add = $request->add_legal_agent_of_owner;

        return in_array((string) $add, ['1', 'true'], true)
            || $add === 1
            || $add === true;
    }

    private function appendStep3AgentData(array $data, Step3Request $request, Contract $contract): array
    {
        $data['id_num_of_property_owner_agent'] = $request->id_num_of_property_owner_agent;
        $data['type_dob_property_owner_agent'] = $request->input('type_dob_property_owner_agent', 'hijri');
        $data['dob_of_property_owner_agent'] = HijriDobParts::combine(
            $request->input('dob_of_property_owner_agent_day'),
            $request->input('dob_of_property_owner_agent_month'),
            $request->input('dob_of_property_owner_agent_year')
        );
        $data['mobile_of_property_owner_agent'] = $request->mobile_of_property_owner_agent;
        $data['agency_number_in_instrument_of_property_owner'] = $request->agency_number_in_instrument_of_property_owner;
        $data['type_agency_instrument_date_of_property_owner'] = $request->input(
            'type_agency_instrument_date_of_property_owner',
            'hijri'
        );
        $data['agency_instrument_date_of_property_owner'] = DateInputNormalizer::combineFromParts(
            $request->input('agency_instrument_date_of_property_owner_day'),
            $request->input('agency_instrument_date_of_property_owner_month'),
            $request->input('agency_instrument_date_of_property_owner_year')
        );

        $data['copy_of_the_authorization_or_agency'] = $request->hasFile('copy_of_the_authorization_or_agency')
            ? $request->file('copy_of_the_authorization_or_agency')->store('authorizations', 'public')
            : $contract->copy_of_the_authorization_or_agency;

        return $data;
    }

    private function appendStep3NoAgentData(array $data): array
    {
        $data['id_num_of_property_owner_agent'] = null;
        $data['type_dob_property_owner_agent'] = null;
        $data['dob_hijri_of_property_owner_agent'] = null;
        $data['mobile_of_property_owner_agent'] = null;
        $data['agency_number_in_instrument_of_property_owner'] = null;
        $data['agency_instrument_date_of_property_owner'] = null;
        $data['type_agency_instrument_date_of_property_owner'] = null;
        $data['copy_of_the_authorization_or_agency'] = null;

        return $data;
    }

    private function syncStep3RealEstateName(Contract $contract, Step3Request $request): void
    {
        if (! $contract->real_id) {
            return;
        }

        if ($contract->instrument_type === 'lease_renewal' && ! $request->filled('name_real_estate')) {
            return;
        }

        RealEstate::query()->whereKey($contract->real_id)->update([
            'name_real_estate' => $request->name_real_estate,
        ]);
    }

    public function step4(Step4Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        if ($contract->instrument_type === 'lease_renewal') {
            if ($contract->is_completed) {
                return $this->errorMessage(trans('api.completed_contract'));
            }

            $contract->update(['step' => 5]);

            return response()->json([
                'message' => trans('api.success'),
                'code' => 200,
                'success' => true,
                'data' => new Step4Resource($contract->fresh()),
            ]);
        }

        $validatedData = $request->validated();

        $validatedData['tenant_dob'] = (
            $request->filled('tenant_dob_day')
            && $request->filled('tenant_dob_month')
            && $request->filled('tenant_dob_year')
        )
            ? HijriDobParts::combine(
                $request->input('tenant_dob_day'),
                $request->input('tenant_dob_month'),
                $request->input('tenant_dob_year')
            )
            : null;

        $validatedData['dob_of_property_tenant_agent'] = (
            $request->filled('dob_of_property_tenant_agent_day')
            && $request->filled('dob_of_property_tenant_agent_month')
            && $request->filled('dob_of_property_tenant_agent_year')
        )
            ? HijriDobParts::combine(
                $request->input('dob_of_property_tenant_agent_day'),
                $request->input('dob_of_property_tenant_agent_month'),
                $request->input('dob_of_property_tenant_agent_year')
            )
            : null;

        unset(
            $validatedData['tenant_dob'],
            $validatedData['tenant_dob_day'],
            $validatedData['tenant_dob_month'],
            $validatedData['tenant_dob_year'],
            $validatedData['dob_of_property_tenant_agent_day'],
            $validatedData['dob_of_property_tenant_agent_month'],
            $validatedData['dob_of_property_tenant_agent_year']
        );

        if ($request->hasFile('copy_of_the_owner_record')) {
            $validatedData['copy_of_the_owner_record'] = $request->file('copy_of_the_owner_record')->store('copy_of_the_owner_record', 'public');
        }

        $data = array_merge($validatedData, [
            'step' => 5,
            'type_tenant_dob' => $request->input('type_tenant_dob', 'hijri'),
            'type_dob_tenant_agent' => $request->input('type_dob_tenant_agent', 'hijri'),
            'copy_of_the_owner_record' => $validatedData['copy_of_the_owner_record'] ?? $contract->copy_of_the_owner_record,
        ]);

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step4Resource($contract->fresh()),
        ]);
    }

        public function step5(Step5Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        $data = [
            'step' => 6,
            'unit_type_id' => $request->unit_type_id,
            'unit_usage_id' => $request->unit_usage_id,
            'unit_number' => $request->unit_number,
            'floor_number' => $request->floor_number,
            'unit_area' => $request->unit_area,
            'tootal_rooms' => $request->tootal_rooms,
            'The_number_of_halls' => $request->The_number_of_halls,
            'The_number_of_kitchens' => $request->The_number_of_kitchens,
            'The_number_of_toilets' => $request->The_number_of_toilets,
            'window_ac' => $request->window_ac,
            'split_ac' => $request->split_ac,
            'electricity_meter_number' => $request->electricity_meter_number,
            'water_meter_number' => $request->water_meter_number,
            'kitchen_tank' => (int) $request->boolean('kitchen_tank'),
            'furnished' => (int) $request->boolean('furnished'),
            'type_furnished' => (int) $request->boolean('type_furnished'),
            'electricity_meter' => (int) $request->boolean('electricity_meter'),
            'water_meter' => (int) $request->boolean('water_meter'),
            'notes_edits' => $request->input('notes_edits'),
        ];

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step5Resource($contract->fresh()),
        ]);
    }

    public function step6(Step6Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $data = [
            'contract_starting_date' => ContractStartingDateInput::resolveForStorage($request),
            'type_contract_starting_date' => $request->input('type_contract_starting_date', 'hijri'),
            'contract_term_in_years' => $request->contract_term_in_years,
            'annual_rent_amount_for_the_unit' => $request->annual_rent_amount_for_the_unit,
            'payment_type_id' => $request->payment_type_id,
            'additional_terms' => $request->additional_terms ?? 0,
            'text_additional_terms' => $request->text_additional_terms,
            'tenant_roles' => $request->tenant_roles ?? 0,
            'step' => 7,
        ];

        [$tenantRoleIds, $firstTenantRoleId] = $this->normalizeTenantRoleIdsFromStep6Request($request);
        $data['tenant_role_ids'] = $tenantRoleIds !== [] ? $tenantRoleIds : null;
        $data['tenant_role_id'] = $firstTenantRoleId;

        if ($request->filled('other_conditions')) {
            $data['other_conditions'] = $request->other_conditions;
        }

        if ($request->filled('daily_fine')) {
            $data['daily_fine'] = $request->daily_fine;
        }

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract' => new ContractResource($contract->fresh(['realEstate'])),
                'price_contract_term' => $contract->contractTermInYears->price ?? null,
            ],
        ]);
    }

    public function financial(string $uuid)
    {
        $userId = auth()->id();

        $contract = Contract::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($uuid) {
                $query->where('uuid', $uuid)->orWhere('id', $uuid);
            })
            ->first();

        if (! $contract) {
            return response()->json([
                'message' => 'العقد غير موجود',
                'success' => false,
                'data' => [],
            ], 404);
        }

        $pricing = ServicesPricing::where('contract_type', $contract->contract_type)->get();
        $totalPricing = $pricing->sum('price');

        $contractCoupon = CouponUsage::where('contract_uuid', $contract->uuid)->first();
        $totalContractPrice = $contractCoupon
            ? $contractCoupon->calculateDiscountedPrice($contract)
            : ($contract->getPriceContractAttribute() + $totalPricing);

        $contractPeriodPrice = ContractPeriod::where('contract_type', $contract->contract_type)
            ->where('id', $contract->contract_term_in_years)
            ->value('price') ?? 0;

        $setting = Setting::first();
        $appFees = $setting ? (int) $setting->application_fees : 0;
        $tax = $setting
            ? ($contract->contract_type === 'housing' ? (int) $setting->housing_tax : (int) $setting->commercial_tax)
            : 0;

        $priceDetails = [
            'contract_period_price' => $contractPeriodPrice,
            'application_fees' => $appFees,
            'tax' => $tax,
        ];

        $services = $pricing->map(function ($service) {
            return [
                'service_name' => $service->name_ar,
                'service_price' => $service->price,
            ];
        })->toArray();

        $finalContractPrice = $totalContractPrice + $contractPeriodPrice;

        $couponAmount = 0;
        if ($contractCoupon) {
            $coupon = Coupon::find($contractCoupon->coupon_id);
            if ($coupon) {
                $couponAmount = $coupon->type_coupon === 'ratio'
                    ? ($totalContractPrice * $coupon->value_coupon / 100)
                    : $coupon->value_coupon;
            }
        }

        $responseData = [
            'price_details' => $priceDetails,
            'services' => $services,
            'total_price' => $finalContractPrice + $couponAmount,
        ];

        if ($contractCoupon) {
            $responseData['coupon'] = $couponAmount;
            $responseData['total_price_after_coupon'] = $finalContractPrice;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'التفاصيل الماليه',
            'data' => $responseData,
        ], 200);
    }
}

