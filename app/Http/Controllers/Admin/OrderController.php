<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContractRequest;
use App\Http\Resources\Admin\V2\Api\AdminContractDetailResource;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use Responser;

    public function orders(Request $request)
    {
        $status = $request->get('status');

        $orders = Contract::query()
            ->when($request->filled('status'), function ($q) use ($status) {
                if (is_numeric($status)) {
                    $q->where('contract_status_id', (int) $status);
                }
            })
            ->when($request->filled('status_name'), fn ($q) =>
                $q->whereHas('contractStatus', fn ($sq) =>
                    $sq->where('name', 'like', '%' . $request->status_name . '%')
                )
            )
            ->when($request->filled('contract_status_id'), fn ($q) =>
                $q->where('contract_status_id', $request->contract_status_id)
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->with([
                'user',
                'receivedContract.employee',
                'contractStatus',
            ])
            ->latest()
            ->paginate($request->get('per_page', 20));
        $orderCollection = OrderResource::collection($orders);
        return $this->apiResponse($orderCollection, trans('api.success'));
    }

    public function incomplete(Request $request)
    {
        try {
            $contracts = $this->incompleteContractsQuery($request)
                ->with($this->contractRelations())
                ->paginate($request->get('per_page', 20));

            return $this->apiResponse(
                OrderResource::collection($contracts),
                trans('api.success')
            );

        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                false,
                500
            );
        }
    }

    private function incompleteContractsQuery(Request $request)
    {
        return Contract::query()
            ->where('is_completed', false)
            ->where('is_delete', false)
            ->when($request->contract_type, fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->user_id, fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->search, fn ($q) =>
                $q->where('uuid', 'like', "%{$request->search}%")
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->orderBy(
                $request->get('sort_by', 'created_at'),
                $request->get('sort_order', 'desc')
            );
    }


    private function contractRelations(): array
    {
        return [
            'user',
            'receivedContract.employee',
            'propertyType',
            'propertyUsages',
            'propertyRegion',
            'propertyCity',
            'unitType',
            'unitUsage',
            'contractTermInYears',
            'paymentType',
        ];
    }

    /**
     * Eager loads for admin single-contract (full payload).
     */
    private function contractDetailRelations(): array
    {
        return [
            'user',
            'realEstate',
            'unit',
            'propertyType',
            'propertyUsages',
            'propertyRegion',
            'propertyCity',
            'tenantEntityLegalRegion',
            'tenantEntityLegalCity',
            'tenantEntityCity',
            'tenantEntityRegion',
            'unitType',
            'unitUsage',
            'contractTermInYears',
            'paymentType',
            'account',
            'receivedContract.employee',
            'contractStatus',
            'contractPayments',
            'tenantRole',
        ];
    }



     public function complete(Request $request)
    {
        try {
            $query = Contract::where('is_completed', true);

            // Filter by contract_type if provided
            if ($request->has('contract_type')) {
                $query->where('contract_type', $request->contract_type);
            }

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Search by UUID if provided
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('uuid', 'like', "%{$search}%");
            }

            // Exclude deleted contracts
            $query->where('is_delete', false);

            $this->applyReceivedContractPresenceToQuery($query, $request);

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $incompleteOrders = $query->with([
                'user',
                'receivedContract.employee',
                'propertyType',
                'propertyUsages',
                'propertyRegion',
                'propertyCity',
                'unitType',
                'unitUsage',
                'contractTermInYears',
                'paymentType'
            ])->paginate($request->get('per_page', 20));

            $orderCollection = OrderResource::collection($incompleteOrders);
              return $this->apiResponse($orderCollection, trans('api.success'));
 
        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                false,
                500
            );
        }
    }

    public function show($id)
    {
        $contract = Contract::with($this->contractDetailRelations())->findOrFail($id);
        $detail = (new AdminContractDetailResource($contract))->toArray(request());

        return $this->apiResponse(
            $this->buildStepBasedDetailResponse($detail),
            trans('api.success')
        );
    }

    /**
     * Split contract detail payload into step-based JSON objects.
     */
    private function buildStepBasedDetailResponse(array $detail): array
    {
        return [
            'contract_summary' => array_merge(Arr::only($detail, [
                'id',
                'uuid',
                'contract_type',
                'instrument_type',
                'image_instrument',
                'image_instrument_from_the_back',
                'image_instrument_from_the_front',
                'is_multiple_trusteeship_deed_copy',
                'copy_of_the_endowment_registration_certificate',
                'copy_of_the_trusteeship_deed',
                'is_completed',
                'status',
                'contract_period_id',
                'documentation_deadline_at',
                'name_owner',
                'type_dob_property_owner',
                'property_owner_id_num',
                'property_owner_iban',
                'property_owner_dob',
                'dob_of_property_owner_agent',
                'id_num_of_property_owner_agent',
                'property_owner_mobile',
                'add_legal_agent_of_owner',
                'type_dob_property_owner_agent',
                'mobile_of_property_owner_agent',
                'agency_number_in_instrument_of_property_owner',
                'type_agency_instrument_date_of_property_owner',
                'agency_instrument_date_of_property_owner',
                'copy_of_the_authorization_or_agency',
            ]), [
                'contract_status_name' => Arr::get($detail, 'contract_status.name'),
                'contract_status_color' => Arr::get($detail, 'contract_status.color'),
                'contract_type' => Arr::get($detail, 'contract_type_trans', Arr::get($detail, 'contract_type')),
                'instrument_type' => Arr::get($detail, 'instrument_type_trans', Arr::get($detail, 'instrument_type')),
                'contract_type_key' => Arr::get($detail, 'contract_type'),
                'instrument_type_key' => Arr::get($detail, 'instrument_type'),
                'contract_period_name' => Arr::get($detail, 'contract_periods.period'),
            ]),
            'step1' => array_merge(Arr::only($detail, [
               
                'building_number',
           
                 'property_place_id',
                'property_city_id',
                'neighborhood',
                'street',
                'postal_code',
                'extra_figure',
                'image_address',
                'latitude',
                'longitude',
                 'property_type_id',
                'property_usages_id',
                'age_of_the_property',
                'number_of_floors',
                'number_of_units_per_floor',
                'number_of_units_in_realestate',
            ]), [
                'property_place_name' => $this->relationName(Arr::get($detail, 'property_region')),
                'city_name' => $this->relationName(Arr::get($detail, 'property_city')),
                'property_type_name' => $this->relationName(Arr::get($detail, 'property_type')),
                'property_usages_name' => $this->relationName(Arr::get($detail, 'property_usages')),
            ]),
            'step2' => array_merge(Arr::only($detail, [
             'unit_type_id',
                'unit_usage_id',
                'unit_number',
                'floor_number',
                'unit_area',
                'tootal_rooms',
                'The_number_of_halls',
                'The_number_of_kitchens',
                'The_number_of_toilets',
                'window_ac',
                'number_of_unit_air_conditioners',
                'split_ac',
                'electricity_meter_number',
                'water_meter_number',
                'kitchen_tank',
                'furnished',
                'type_furnished',
                'electricity_meter',
                'water_meter',
                'unit',
            ]), [
                'unit_type_name' => $this->relationName(Arr::get($detail, 'unit_type')),
                'unit_usage_name' => $this->relationName(Arr::get($detail, 'unit_usage')),
            ]),
            'step3' => array_merge(Arr::only($detail, [
               'tenant_name',
                'type_tenant_dob',
                'tenant_id_number',
                'tenant_dob',
                'tenant_mobile',
                'tenant_email',
                'tenant_nationality',
                'tenant_work',
                'tenant_gender',
                'is_there_a_legal_representative_of_the_tenant',
                'id_number_of_property_tenant_agent',
                'type_dob_tenant_agent',
                'dob_of_property_tenant_agent',
                'mobile_of_property_tenant_agent',
                'copy_of_the_owner_record',
                'tenant_role_id',
                'tenant_role',
            ]), [
                'tenant_role_name' => Arr::get($detail, 'tenant_role.name'),
            ]),
            'step4' => array_merge(Arr::only($detail, [
                'contract_starting_date',
                'type_contract_starting_date',
                'contract_term_in_years',
                'annual_rent_amount_for_the_unit',
                'payment_type_id',
                'additional_terms',
                'text_additional_terms',
                'notes_edits',
                'tenant_roles',
                'tenant_role_ids',
                'tenant_role_id',
                'other_conditions',
                'daily_fine',
                'payment_type',
            ]), [
                'contract_term_name' => $this->relationName(Arr::get($detail, 'contract_term_in_years')),
                'payment_type_name' => $this->relationName(Arr::get($detail, 'payment_type')),
                'tenant_role_name' => $this->relationName(Arr::get($detail, 'tenant_role')),
            ]),
          
            
            'payment_and_admin' => Arr::only($detail, [
                'account',
                'contract_payments',
                'received_contract',
                'relation_labels',
                'created_at',
                'updated_at',
            ]),
        ];
    }

    private function relationName(mixed $relation): ?string
    {
        if (! is_array($relation)) {
            return null;
        }

        // Return one display value only (localized name; Arabic by default via app locale).
        return $relation['name'] ?? $relation['name_ar'] ?? $relation['name_en'] ?? null;
    }

    public function updateContractStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_status_id' => ['required', 'integer', 'exists:contract_statuses,id'],
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $contract = Contract::findOrFail($id);
            $contract->update([
                'contract_status_id' => (int) $request->contract_status_id,
            ]);
            $contract->load($this->contractDetailRelations());

            return $this->apiResponse(
                new AdminContractDetailResource($contract),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                null,
                trans('api.contract_not_found'),
                false,
                404
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                false,
                500
            );
        }
    }

     public function update(UpdateContractRequest $request, $id)
    {
        try {
            $contract = Contract::findOrFail($id);
            $validatedData = $request->validated();
            $contract->update($validatedData);
            $contract->load($this->contractDetailRelations());

            return $this->apiResponse(
                new AdminContractDetailResource($contract),
                trans('api.contract_updated_successfully')
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                null,
                trans('api.contract_not_found'),
                false,
                404
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Filter by whether `received_contracts.contract_id` matches this contract (`contracts.id`).
     * Relation `receivedContract` is hasOne → same table/column.
     *
     * Query params (first match wins):
     * - `is_received=1|true|yes` → row exists in `received_contracts`.
     * - `is_received=0|false|no` → no row for this contract id.
     * - Same semantics for legacy `received_contract=…`.
     * Omit both → no filter.
     */
    private function applyReceivedContractPresenceToQuery($query, Request $request): void
    {
        $wantReceived = $this->parseReceivedContractQueryFilter($request);
        if ($wantReceived === null) {
            return;
        }

        if ($wantReceived) {
            $query->whereHas('receivedContract');
        } else {
            $query->whereDoesntHave('receivedContract');
        }
    }

    private function parseReceivedContractQueryFilter(Request $request): ?bool
    {
        if ($request->has('is_received')) {
            $raw = $request->query('is_received');
            if ($raw !== null && $raw !== '') {
                return $request->boolean('is_received');
            }
        }

        if (! $request->has('received_contract')) {
            return null;
        }

        $raw = $request->query('received_contract');
        if ($raw === null || $raw === '') {
            return null;
        }

        return $request->boolean('received_contract');
    }


}