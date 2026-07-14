<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Contract\ContractTypeRequest;
use App\Http\Requests\Api\V2\Contract\DocFeePreviewRequest;
use App\Http\Requests\Api\V2\Contract\SetContractDraftRequest;
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
use App\Http\Resources\Api\V2\Contract\Step6Resource;
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
use App\Support\DocFee;
use App\Support\HijriDobParts;
use App\Support\MeterFees;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    use Responser;
    public function index()
    {
        $user = auth()->user();
        $contracts = Contract::where('user_id', $user->id)
            ->with(['realEstate', 'contractStatus', 'draftContractStatus'])
            ->orderBy('updated_at', 'desc')
            ->where('is_delete', 0)
            ->reachedAdminOrderStep()
            ->paginate(10);

        return $this->apiResponse(
            [
                'data' => ContractResource::collection($contracts),
                
                'pagination' => $this->paginate($contracts),
            ],
            trans('api.success')
        );
    }

    public function byStatus(Request $request, $statusId)
    {
        $request->merge(['status_id' => $statusId]);
        $this->validate($request, [
            'status_id' => 'required|integer|exists:contract_statuses,id',
        ]);

        $user = auth()->user();
        $contracts = Contract::query()
            ->where('user_id', $user->id)
            ->where('contract_status_id', (int) $statusId)
            ->where('is_delete', 0)
            ->reachedAdminOrderStep()
            ->with(['realEstate', 'contractStatus', 'draftContractStatus'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return $this->apiResponse(
            [
                'data' => ContractResource::collection($contracts),
                'pagination' => $this->paginate($contracts),
            ],
            trans('api.success')
        );
    }

    public function drafts()
    {
        $user = auth()->user();
        $contracts = Contract::query()
            ->where('user_id', $user->id)
            ->where('is_delete', 0)
            ->reachedAdminOrderStep()
            ->draft()
            ->with(['realEstate', 'contractStatus', 'draftContractStatus'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return $this->apiResponse(
            [
                'data' => ContractResource::collection($contracts),
                'pagination' => $this->paginate($contracts),
            ],
            trans('api.success')
        );
    }

    public function draftsByStatus(Request $request, $statusId)
    {
        $request->merge(['status_id' => $statusId]);
        $this->validate($request, [
            'status_id' => 'required|integer|exists:draft_contract_statuses,id',
        ]);

        $user = auth()->user();
        $contracts = Contract::query()
            ->where('user_id', $user->id)
            ->where('is_delete', 0)
            ->reachedAdminOrderStep()
            ->draft()
            ->where('draft_contract_status_id', (int) $statusId)
            ->with(['realEstate', 'contractStatus', 'draftContractStatus'])
            ->orderBy('updated_at', 'desc')
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
        $contract = Contract::with(['realEstate', 'contractStatus', 'draftContractStatus'])
            ->where('user_id', $user->id)
            ->reachedAdminOrderStep()
            ->findOrFail($id);

        return $this->apiResponse(new ContractResource($contract), trans('api.success'));
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $contract = Contract::query()
            ->where('user_id', $user->id)
            ->notDeleted()
            ->find($id);

        if (! $contract) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        }

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $contract->update(['is_delete' => true]);

        return $this->successMessage(trans('api.deleted_successfully'), 200);
    }

    public function start(ContractTypeRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $instrumentType = $request->filled('instrument_type')
            ? ($validated['instrument_type'] ?? null)
            : null;

        if (! $instrumentType && ! empty($validated['real_id'])) {
            $instrumentType = RealEstate::query()
                ->whereKey($validated['real_id'])
                ->value('instrument_type');
        }

        if (! empty($validated['real_id'])) {
            $realEstate = RealEstate::query()->find($validated['real_id']);
            $realEstate?->syncNumberOfUnitsInRealestate(
                ! empty($validated['real_units_id']) ? (int) $validated['real_units_id'] : null
            );
        }

        $contract = Contract::create([
            'contract_type' => $validated['contract_type'],
            'instrument_type' => $instrumentType,
            'is_real' => (bool) ($validated['is_real'] ?? false),
            'real_id' => $validated['real_id'] ?? null,
            'real_units_id' => $validated['real_units_id'] ?? null,
            'user_id' => $user->id,
            'step' => Contract::shouldSkipInitialSteps($instrumentType) ? 3 : 1,
        ]);

        return $this->apiResponse(
            [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
            trans('api.success')
        );
    }

    private function notifyEmployeesNewContract(Contract $contract, ?float $paidAmount = null): void
    {
        try {
            app(FirebaseNotificationService::class)->notifyEmployeesOfNewContract($contract, $paidAmount);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify employees of new contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function step1(Step1Request $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        $contract = Contract::where('user_id', $user->id)->findOrFail($validated['id']);

        $step1Data = [
            'app_or_web' => 'app',
            'instrument_number' => null,
            'is_multiple_trusteeship_deed_copy' => array_key_exists('is_multiple_trusteeship_deed_copy', $validated)
                ? (bool) $validated['is_multiple_trusteeship_deed_copy']
                : (bool) $contract->is_multiple_trusteeship_deed_copy,
        ];

        foreach ([
            'property_type_id',
            'property_usages_id',
            'age_of_the_property',
            'number_of_floors',
            'number_of_units_per_floor',
            'number_of_units_in_realestate',
        ] as $optionalField) {
            if (array_key_exists($optionalField, $validated)) {
                $step1Data[$optionalField] = $validated[$optionalField];
            }
        }

        if ($request->filled('instrument_type')) {
            $step1Data['instrument_type'] = $validated['instrument_type'];
        }

        $this->applyCoordinatesIfPresent($step1Data, $request, $validated);
        $this->applyAddressUrlIfPresent($step1Data, $request, $validated);

        if ($addressError = $this->applyPropertyAddressIfPresent($step1Data, $request, $validated, $contract)) {
            return $addressError;
        }

        $effectiveInstrumentType = $step1Data['instrument_type'] ?? $contract->instrument_type;
        $step1Data['step'] = Contract::shouldSkipInitialSteps($effectiveInstrumentType) ? 3 : 2;

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

        foreach (['image_instrument_from_the_front', 'image_instrument_from_the_back'] as $deedImageField) {
            if ($request->hasFile($deedImageField)) {
                $contract->update([
                    $deedImageField => $request->file($deedImageField)->store('images/contracts', 'public'),
                ]);
            } elseif (
                array_key_exists($deedImageField, $validated)
                && is_string($validated[$deedImageField])
                && $validated[$deedImageField] !== ''
            ) {
                $contract->update([
                    $deedImageField => $validated[$deedImageField],
                ]);
            }
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

        if ($request->hasFile('image_address')) {
            $contract->update([
                'image_address' => $request->file('image_address')->store('images/contracts', 'public'),
            ]);
        }

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step1Resource($contract->fresh(['realEstate', 'contractStatus'])),
        ], 200);
    }
 
   

    public function step2(Step2Request $request)
    {
        $validated = $request->validated();
        $contract = Contract::findOrFail($validated['id']);

        if (Contract::shouldSkipInitialSteps($contract->instrument_type)) {
            $skipData = ['step' => 3];
            $this->applyCoordinatesIfPresent($skipData, $request, $validated);
            $this->applyAddressUrlIfPresent($skipData, $request, $validated);
            if ($addressError = $this->applyPropertyAddressIfPresent($skipData, $request, $validated, $contract)) {
                return $addressError;
            }
            $contract->update($skipData);

            return response()->json([
                'message' => trans('api.success'),
                'code' => 200,
                'success' => true,
                'data' => new Step2Resource($contract->fresh(['contractStatus'])),
            ]);
        }

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $data = ['step' => 3];
        $this->mergeRequiredPropertyAddress($data, $validated);
        if ($addressError = $this->applyPropertyAddressIfPresent($data, $request, $validated, $contract)) {
            return $addressError;
        }

        $this->applyCoordinatesIfPresent($data, $request, $validated);
        $this->applyAddressUrlIfPresent($data, $request, $validated);

        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/contracts', 'public');
        }

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step2Resource($contract->fresh(['contractStatus'])),
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
            'data' => new Step3Resource($contract->fresh(['contractStatus'])),
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
        $data['dob_of_property_owner_agent'] = null;
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

            $leaseRenewalData = ['step' => 5];
            if ($request->has('notes_edits')) {
                $leaseRenewalData['notes_edits'] = $request->input('notes_edits');
            }

            $contract->update($leaseRenewalData);

            return response()->json([
                'message' => trans('api.success'),
                'code' => 200,
                'success' => true,
                'data' => new Step4Resource($contract->fresh(['contractStatus'])),
            ]);
        }

        $validatedData = $request->validated();

        $tenantDobCombined = (
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

        $tenantAgentDobCombined = (
            $request->filled('dobof_property_tenant_agent_day')
            && $request->filled('dobof_property_tenant_agent_month')
            && $request->filled('dobof_property_tenant_agent_year')
        )
            ? HijriDobParts::combine(
                $request->input('dobof_property_tenant_agent_day'),
                $request->input('dobof_property_tenant_agent_month'),
                $request->input('dobof_property_tenant_agent_year')
            )
            : null;

        unset(
            $validatedData['tenant_dob'],
            $validatedData['tenant_dob_day'],
            $validatedData['tenant_dob_month'],
            $validatedData['tenant_dob_year'],
            $validatedData['dobof_property_tenant_agent_day'],
            $validatedData['dobof_property_tenant_agent_month'],
            $validatedData['dobof_property_tenant_agent_year']
        );

        if ($request->hasFile('copy_of_the_owner_record')) {
            $validatedData['copy_of_the_owner_record'] = $request->file('copy_of_the_owner_record')->store('copy_of_the_owner_record', 'public');
        }

        $data = array_merge($validatedData, [
            'step' => 5,
            'tenant_dob' => $tenantDobCombined,
            'dob_of_property_tenant_agent' => $tenantAgentDobCombined,
            'type_tenant_dob' => $request->input('type_tenant_dob', 'hijri'),
            'type_dob_tenant_agent' => $request->input('type_dob_tenant_agent', 'hijri'),
            'copy_of_the_owner_record' => $validatedData['copy_of_the_owner_record'] ?? $contract->copy_of_the_owner_record,
        ]);

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step4Resource($contract->fresh(['contractStatus'])),
        ]);
    }

        public function step5(Step5Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        $data = [
            'step' => 6,
            'unit_type_id' => $request->unit_type_id,
            'unit_number' => $request->unit_number,
            'floor_number' => $request->floor_number,
            'unit_area' => $request->unit_area,
            'tootal_rooms' => $request->tootal_rooms,
            'The_number_of_halls' => $request->The_number_of_halls,
            'number_of_councils' => $request->number_of_councils,
            'The_number_of_kitchens' => $request->The_number_of_kitchens,
            'The_number_of_toilets' => $request->The_number_of_toilets,
            'window_ac' => $request->window_ac,
            'split_ac' => $request->split_ac,
            'electricity_meter_number' => $request->electricity_meter_number,
            'water_meter_number' => $request->water_meter_number,
            'kitchen_tank' => (int) $request->boolean('kitchen_tank'),
            'furnished' => (int) $request->boolean('furnished'),
            'type_furnished' => \App\Support\TypeFurnished::normalize($request->type_furnished),
            'electricity_meter' => (int) $request->boolean('electricity_meter'),
            'water_meter' => (int) $request->boolean('water_meter'),
            'electricity_meter_ownership' => $request->input('electricity_meter_ownership'),
            'water_meter_ownership' => $request->input('water_meter_ownership'),
        ];

        if ($request->exists('unit_usage_id')) {
            $data['unit_usage_id'] = $request->input('unit_usage_id');
        }

        $contract->update($data);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step5Resource($contract->fresh(['contractStatus'])),
        ]);
    }

    public function step6(Step6Request $request)
    {
        $contract = Contract::findOrFail($request->id);

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $isOther = $request->input('duration_preset') === 'other';
        $docFee = null;

        $data = [
            'contract_starting_date' => ContractStartingDateInput::resolveForStorage($request),
            'type_contract_starting_date' => $request->input('type_contract_starting_date', 'hijri'),
            'payment_type_id' => $request->payment_type_id,
            'additional_terms' => $request->additional_terms ?? 0,
            'text_additional_terms' => $request->text_additional_terms,
            'tenant_roles' => $request->boolean('tenant_roles'),
            'step' => 7,
        ];

        if ($request->filled('annual_rent_amount_for_the_unit')) {
            $data['annual_rent_amount_for_the_unit'] = $request->annual_rent_amount_for_the_unit;
        }

        if ($isOther) {
            $years = (int) $request->input('duration_years', 0);
            $months = (int) $request->input('duration_months', 0);
            $docFee = DocFee::summarize((string) $contract->contract_type, 'other', $years, $months);

            $data['duration_preset'] = 'other';
            $data['duration_years'] = $docFee['duration_years'];
            $data['duration_months'] = $docFee['duration_months'];
            $data['total_months'] = $docFee['total_months'];
            // المدة الأساسية مش مطلوبة مع مدة أخرى
            $data['contract_term_in_years'] = $request->filled('contract_term_in_years')
                ? $request->contract_term_in_years
                : null;
        } else {
            $data['contract_term_in_years'] = $request->contract_term_in_years;
            // امسح مسار مدة أخرى لو رجع لزر جاهز
            $data['duration_preset'] = null;
            $data['duration_years'] = null;
            $data['duration_months'] = null;
            $data['total_months'] = null;
        }

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
        $contract = $contract->fresh(['realEstate', 'contractStatus', 'contractTermInYears']);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step6Resource($contract),
        ]);
    }

    /**
     * معاينة نص رسوم التوثيق + المبلغ حسب سنة/شهر (مدة أخرى) — بدون حفظ.
     * POST /api/v2/contract/doc-fee
     *
     * Body: duration_years, duration_months + (contract_type أو id)
     */
    public function docFeePreview(DocFeePreviewRequest $request)
    {
        $summary = DocFee::summarize(
            (string) $request->input('contract_type'),
            'other',
            (int) $request->input('duration_years'),
            (int) $request->input('duration_months'),
        );

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'duration_years' => $summary['duration_years'],
                'duration_months' => $summary['duration_months'],
                'total_months' => $summary['total_months'],
                'billable_years' => $summary['billable_years'],
                'has_extra_months' => $summary['has_extra_months'],
                'amount' => $summary['doc_fee'],
                'doc_fee' => $summary['doc_fee'],
                'doc_fee_lines' => $summary['doc_fee_lines'],
                'text' => implode("\n", $summary['doc_fee_lines']),
            ],
        ]);
    }

    public function setDraft(SetContractDraftRequest $request)
    {
        $user = auth()->user();
        $contract = Contract::where('user_id', $user->id)->findOrFail($request->id);

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $wasDraft = (bool) $contract->is_draft;
        $isDraft = $request->boolean('is_draft');

        $contract->update([
            'is_draft' => $isDraft,
        ]);

        // Notify admins when the user submits a draft for the first time.
        if ($isDraft && ! $wasDraft) {
            $this->notifyEmployeesNewContract($contract->fresh(['user']));
        }

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new ContractResource($contract->fresh(['realEstate', 'contractStatus'])),
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

        $docFeeSummary = DocFee::forContract($contract);
        $docFeeAmount = $docFeeSummary['doc_fee'] ?? null;

        $legacyPeriodPrice = ContractPeriod::where('contract_type', $contract->contract_type)
            ->where('id', $contract->contract_term_in_years)
            ->value('price') ?? 0;

        $contractPeriodPrice = $docFeeAmount !== null ? (float) $docFeeAmount : (float) $legacyPeriodPrice;

        $setting = Setting::first();
        $appFees = $setting ? (int) $setting->application_fees : 0;
        $tax = $setting
            ? ($contract->contract_type === 'housing' ? (int) $setting->housing_tax : (int) $setting->commercial_tax)
            : 0;

        $meterFees = MeterFees::forContract($contract, $setting);

        $priceDetails = [
            'contract_period_price' => $contractPeriodPrice,
            'application_fees' => $appFees,
            'tax' => $tax,
            'electricity_meter_fee' => $meterFees['electricity_meter_fee'],
            'water_meter_fee' => $meterFees['water_meter_fee'],
        ];

        if ($docFeeSummary) {
            $priceDetails['doc_fee'] = $docFeeSummary['doc_fee'];
            $priceDetails['billable_years'] = $docFeeSummary['billable_years'];
            $priceDetails['total_months'] = $docFeeSummary['total_months'];
            $priceDetails['has_extra_months'] = $docFeeSummary['has_extra_months'];
            $priceDetails['duration_preset'] = $docFeeSummary['duration_preset'];
            $priceDetails['duration_years'] = $docFeeSummary['duration_years'];
            $priceDetails['duration_months'] = $docFeeSummary['duration_months'];
        }

        $services = $pricing->map(function ($service) {
            return [
                'service_name' => $service->name_ar,
                'service_price' => $service->price,
            ];
        })->toArray();

        $finalContractPrice = $totalContractPrice + $contractPeriodPrice + $meterFees['meter_fees_total'];

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
            'meter_fees_total' => $meterFees['meter_fees_total'],
            'total_price' => $finalContractPrice + $couponAmount,
        ];

        if ($docFeeSummary) {
            $responseData['doc_fee'] = $docFeeSummary['doc_fee'];
            $responseData['doc_fee_lines'] = $docFeeSummary['doc_fee_lines'];
            $responseData['billable_years'] = $docFeeSummary['billable_years'];
            $responseData['has_extra_months'] = $docFeeSummary['has_extra_months'];
        }

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

    private const PROPERTY_ADDRESS_FIELDS = [
        'property_place_id',
        'property_city_id',
        'neighborhood',
        'street',
        'building_number',
        'postal_code',
        'extra_figure',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function mergeRequiredPropertyAddress(array &$payload, array $validated): void
    {
        foreach (self::PROPERTY_ADDRESS_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }
    }

    /**
     * Persist address fields only when the client sends them (step1 / optional step2 skip).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function applyPropertyAddressIfPresent(
        array &$payload,
        Request $request,
        array $validated = [],
        ?Contract $contract = null
    ): ?\Illuminate\Http\JsonResponse {
        foreach (self::PROPERTY_ADDRESS_FIELDS as $field) {
            if ($request->filled($field)) {
                $payload[$field] = $validated[$field] ?? $request->input($field);
            }
        }

        $placeId = $payload['property_place_id'] ?? $contract?->property_place_id;
        $cityId = $payload['property_city_id'] ?? $contract?->property_city_id;

        if ($placeId && $cityId) {
            $cityBelongsToRegion = City::query()
                ->whereKey($cityId)
                ->where('region_id', $placeId)
                ->exists();

            if (! $cityBelongsToRegion) {
                return $this->errorMessage(trans('api.city_not_include_region'));
            }
        }

        return null;
    }

    /**
     * Persist address_url only when the client sends it (avoid wiping with null).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function applyAddressUrlIfPresent(array &$payload, Request $request, array $validated = []): void
    {
        if ($request->filled('address_url')) {
            $payload['address_url'] = $validated['address_url'] ?? $request->input('address_url');
        }
    }

    /**
     * Persist latitude/longitude only when the client sends them (avoid wiping with null).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function applyCoordinatesIfPresent(array &$payload, Request $request, array $validated = []): void
    {
        $latitude = $this->resolveCoordinateInput($request, $validated, 'latitude', 'lat');
        if ($latitude !== null) {
            $payload['latitude'] = $latitude;
        }

        $longitude = $this->resolveCoordinateInput($request, $validated, 'longitude', 'lng');
        if ($longitude !== null) {
            $payload['longitude'] = $longitude;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCoordinateInput(
        Request $request,
        array $validated,
        string $canonicalKey,
        string $aliasKey
    ): ?float {
        if ($request->filled($canonicalKey)) {
            return (float) ($validated[$canonicalKey] ?? $request->input($canonicalKey));
        }

        if ($request->filled($aliasKey)) {
            return (float) $request->input($aliasKey);
        }

        return null;
    }
}

