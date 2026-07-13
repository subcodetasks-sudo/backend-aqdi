<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContractRequest;
use App\Http\Requests\Admin\UpdateReturnContractAcceptanceRequest;
use App\Http\Resources\Admin\V2\Api\AdminContractDetailResource;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\TenantRole;
use App\Services\Admin\RefundableContractService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use Responser;

    public function orders(Request $request)
    {
        $orders = Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->tap(fn ($q) => $this->applyContractStatusFiltersToQuery($q, $request))
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->with($this->orderListRelations())
            ->latest()
            ->paginate($this->perPageFromRequest($request, 120, 200));

        return $this->paginatedApiResponse(
            $orders,
            OrderResource::collection($orders)
        );
    }

    /**
     * Returned contracts (contract_status_id = 2).
     * GET /api/admin/orders/return
     */
    public function returnOrders(Request $request)
    {
        try {
            $contracts = $this->returnContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['contract_status_id' => RefundableContractService::RETURN_CONTRACT_STATUS_ID]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Received contracts only (row exists in received_contracts).
     * GET /api/admin/orders/received
     */
    public function receivedOrders(Request $request)
    {
        try {
            $contracts = Contract::query()
                ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
                ->notDeleted()
                ->whereHas('receivedContract')
                ->tap(fn ($q) => $this->applyContractStatusFiltersToQuery($q, $request))
                ->when($request->filled('contract_type'), fn ($q) =>
                    $q->where('contract_type', $request->contract_type)
                )
                ->when($request->filled('user_id'), fn ($q) =>
                    $q->where('user_id', $request->user_id)
                )
                ->when($request->filled('search'), fn ($q) =>
                    $q->adminSearch($request->string('search')->toString())
                )
                ->with($this->orderListRelations())
                ->orderBy(
                    $request->get('sort_by', 'created_at'),
                    $request->get('sort_order', 'desc')
                )
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['is_received' => true]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Contracts filtered by contract_status_id.
     * GET /api/admin/orders/status/{statusId}
     */
    public function byStatus(Request $request, $statusId)
    {
        $request->merge(['status_id' => $statusId]);
        $this->validate($request, [
            'status_id' => 'required|integer|exists:contract_statuses,id',
        ]);

        try {
            $contracts = Contract::query()
                ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
                ->notDeleted()
                ->where('contract_status_id', (int) $statusId)
                ->when($request->filled('search'), fn ($q) =>
                    $q->adminSearch($request->string('search')->toString())
                )
                ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
                ->with($this->orderListRelations())
                ->latest()
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['contract_status_id' => (int) $statusId]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Draft contracts filtered by draft_contract_status_id.
     * GET /api/admin/orders/draft/status/{statusId}
     */
    public function draftByStatus(Request $request, $statusId)
    {
        $request->merge(['status_id' => $statusId]);
        $this->validate($request, [
            'status_id' => 'required|integer|exists:draft_contract_statuses,id',
        ]);

        try {
            $contracts = Contract::query()
                ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
                ->notDeleted()
                ->draft()
                ->where('draft_contract_status_id', (int) $statusId)
                ->when($request->filled('search'), fn ($q) =>
                    $q->adminSearch($request->string('search')->toString())
                )
                ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
                ->with($this->orderListRelations())
                ->latest()
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['is_draft' => true, 'draft_contract_status_id' => (int) $statusId]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    public function incomplete(Request $request)
    {
        try {
            $contracts = $this->incompleteContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['is_completed' => 0, 'is_delete' => 0]
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

    /**
     * Draft contracts (is_draft = true).
     * GET /api/admin/contracts/draft
     */
    public function draftContracts(Request $request)
    {
        try {
            $contracts = $this->draftContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['is_draft' => true]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Completed and draft contracts together.
     * GET /api/admin/orders/completed-draft
     */
    public function completedAndDraft(Request $request)
    {
        try {
            $contracts = $this->completedAndDraftContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                ['is_completed_or_draft' => true]
            );
        } catch (\Throwable $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    private function returnContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->where('contract_status_id', RefundableContractService::RETURN_CONTRACT_STATUS_ID)
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->orderBy(
                $request->get('sort_by', 'created_at'),
                $request->get('sort_order', 'desc')
            );
    }

    private function orderListRelations(): array
    {
        return [
            'user',
            'receivedContract.employee',
            'acceptRetrunContractEmployee:id,name',
            'refundableContract',
            'contractStatus',
            'draftContractStatus',
            'contractPayments' => fn ($q) => $q->where('status', 'success'),
        ];
    }

    private function incompleteContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->incomplete()
            ->tap(fn ($q) => $this->applyContractStatusFiltersToQuery($q, $request))
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->orderBy(
                $request->get('sort_by', 'created_at'),
                $request->get('sort_order', 'desc')
            );
    }

    private function draftContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->draft()
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->orderBy(
                $request->get('sort_by', 'created_at'),
                $request->get('sort_order', 'desc')
            );
    }

    private function completedAndDraftContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->where(function ($q) {
                $q->where('is_completed', 1)
                    ->orWhere('is_draft', true);
            })
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
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
            'contractStatus',
            'contractPayments' => fn ($q) => $q->where('status', 'success'),
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
            'acceptRetrunContractEmployee:id,name',
            'refundableContract',
            'contractStatus',
            'draftContractStatus',
            'contractPayments',
            'tenantRole',
        ];
    }



     public function complete(Request $request)
    {
        try {
            $completedOrders = $this->completeContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $completedOrders,
                OrderResource::collection($completedOrders),
                trans('api.success'),
                ['is_completed' => 1, 'is_delete' => 0]
            );
 
        } catch (\Exception $e) {
            return $this->apiResponse(
                null,
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                false,
                500
            );
        }
    }

    private function completeContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->completed()
            ->tap(fn ($q) => $this->applyContractStatusFiltersToQuery($q, $request))
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->orderBy(
                $request->get('sort_by', 'created_at'),
                $request->get('sort_order', 'desc')
            );
    }

    /**
     * Shared filters: status, status_name, contract_status_id, is_completed.
     */
    private function applyContractStatusFiltersToQuery($query, Request $request): void
    {
        $status = $request->get('status');

        $query
            ->when($request->has('is_completed'), fn ($q) =>
                $q->where('is_completed', $request->boolean('is_completed') ? 1 : 0)
            )
            ->when($request->filled('status'), function ($q) use ($status) {
                if (is_numeric($status)) {
                    $q->where('contract_status_id', (int) $status);
                } elseif (in_array(strtolower((string) $status), ['incomplete', 'uncompleted', 'not_completed'], true)) {
                    $q->incomplete();
                } elseif (in_array(strtolower((string) $status), ['complete', 'completed'], true)) {
                    $q->completed();
                }
            })
            ->when($request->filled('status_name'), fn ($q) =>
                $q->whereHas('contractStatus', fn ($sq) =>
                    $sq->where('name', 'like', '%'.$request->status_name.'%')
                )
            )
            ->when($request->filled('contract_status_id'), fn ($q) =>
                $q->where('contract_status_id', $request->contract_status_id)
            );
    }

    public function show($id)
    {
        $contract = $this->findAdminContract((int) $id);
        $contract->load($this->contractDetailRelations());
        $detail = (new AdminContractDetailResource($contract))->toArray(request());

        return $this->apiResponse(
            array_merge(
                $detail,
                $this->buildStepBasedDetailResponse($detail),
                [
                    'user_contracts' => $this->userContractSummariesForUser($contract->user_id),
                ]
            ),
            trans('api.success')
        );
    }

    /**
     * @return array<int, array{id: int, uuid: string}>
     */
    private function userContractSummariesForUser(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return Contract::query()
            ->where('user_id', $userId)
            ->notDeleted()
            ->orderByDesc('id')
            ->get(['id', 'uuid'])
            ->map(static fn (Contract $contract) => [
                'id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ])
            ->values()
            ->all();
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
                'is_draft',
                'status',
                'contract_period_id',
                'documentation_deadline_at',
                'name_owner',
                'type_dob_property_owner',
                'property_owner_id_num',
                'property_owner_iban',
                'property_owner_dob',
                'id_num_of_property_owner_agent',
                'property_owner_mobile',
                'add_legal_agent_of_owner',
                'type_dob_property_owner_agent',
                'dob_of_property_owner_agent',
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
                'contract_period' => Arr::get($detail, 'contract_period.period'),
                'accept_retrun_contract' => (bool) Arr::get($detail, 'accept_retrun_contract', false),
                'accept_retrun_contract_employee_id' => Arr::get($detail, 'accept_retrun_contract_employee_id'),
                'accept_retrun_contract_employee' => Arr::get($detail, 'accept_retrun_contract_employee'),
                'return_contract' => (bool) Arr::get($detail, 'return_contract', false),
                'draft_contract_number' => Arr::get($detail, 'draft_contract_number'),
                'refund_amount' => Arr::get($detail, 'refund_amount'),
            ]),
            'step1' => array_merge(Arr::only($detail, [
               
                'building_number',
                 'property_place_id',
                'property_city_id',
                'neighborhood',
                'street',
                'postal_code',
                'extra_figure',
                'address_url',
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
                'number_of_councils',
                'The_number_of_kitchens',
                'The_number_of_the_toilet',
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
                'tenant_id_num',
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
                'tenant_role_names' => $this->tenantRoleNames($detail),
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
                'tenant_role_names' => $this->tenantRoleNames($detail),
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

    /**
     * Return tenant role labels as array (one or many).
     *
     * @param  array<string, mixed>  $detail
     * @return array<int, string>
     */
    private function tenantRoleNames(array $detail): array
    {
        $ids = Arr::get($detail, 'tenant_role_ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids))));

        if ($ids !== []) {
            $names = TenantRole::query()
                ->whereIn('id', $ids)
                ->orderByRaw('FIELD(id,'.implode(',', $ids).')')
                ->pluck('text_of_reason')
                ->filter(static fn ($v) => is_string($v) && trim($v) !== '')
                ->map(static fn ($v) => trim((string) $v))
                ->values()
                ->all();

            if ($names !== []) {
                return $names;
            }
        }

        $single = Arr::get($detail, 'tenant_role.name');
        if (is_string($single) && trim($single) !== '') {
            return [trim($single)];
        }

        return [];
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

            $contract = $this->findAdminContract((int) $id);
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

    /**
     * Update draft contract status (مسودة).
     * POST /api/admin/orders/{id}/draft-contract-status
     */
    public function updateDraftContractStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'draft_contract_status_id' => ['required', 'integer', 'exists:draft_contract_statuses,id'],
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $contract = $this->findAdminContract((int) $id);

            if (! $contract->is_draft) {
                return $this->errorMessage('العقد ليس مسودة.', 422);
            }

            $contract->update([
                'draft_contract_status_id' => (int) $request->draft_contract_status_id,
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
                trans('api.error_occurred').': '.$e->getMessage(),
                false,
                500
            );
        }
    }

    /**
     * Partial contract update — send only fields to change (all nullable).
     * POST /api/admin/orders/{id}
     */
    public function update(UpdateContractRequest $request, $id)
    {
        try {
            $contract = $this->findAdminContract((int) $id);

            $payload = $request->updatePayload();

            $contract->fill($payload);
            $contract->save();
            $contract->refresh();
            $contract->load($this->contractDetailRelations());

            $detail = (new AdminContractDetailResource($contract))->toArray($request);

            return $this->apiResponse(
                $this->buildStepBasedDetailResponse($detail),
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
    /**
     * Set return-contract acceptance (مسترجع) — employee Bearer required.
     *
     * POST /api/admin/orders/{id}/return-contract-status
     * Body: { "accept_retrun_contract": true }
     */
    public function updateReturnContractAcceptance(UpdateReturnContractAcceptanceRequest $request, int $id)
    {
        return $this->setReturnContractAcceptance(
            $request,
            $id,
            $request->boolean('accept_retrun_contract')
        );
    }

    private function setReturnContractAcceptance(Request $request, int $id, bool $accepted)
    {
        try {
            if (! $request->user() instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            /** @var Employee $employee */
            $employee = $request->user();

            $contract = $this->findAdminContract($id);

            if ((int) $contract->contract_status_id !== RefundableContractService::RETURN_CONTRACT_STATUS_ID) {
                return $this->errorMessage(trans('api.refund_contract_must_be_return_status'), 422);
            }

            $contract->update([
                'accept_retrun_contract' => $accepted,
                'accept_retrun_contract_employee_id' => $employee->id,
            ]);

            $contract->load($this->contractDetailRelations());
            $detail = (new AdminContractDetailResource($contract))->toArray($request);

            return $this->apiResponse(
                $this->buildStepBasedDetailResponse($detail),
                $accepted
                    ? trans('api.return_contract_accepted_successfully')
                    : trans('api.return_contract_rejected_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                null,
                trans('api.contract_not_found'),
                false,
                404
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Load contract by id for admin write/show (includes soft-deleted rows).
     */
    private function findAdminContract(int $id): Contract
    {
        return Contract::query()->whereKey($id)->firstOrFail();
    }

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

    /**
     * Subquery: payments.amount for success status where contract_uuid matches contract uuid (with optional "-suffix").
     */
    private function applySuccessfulPaymentAmountSelect($query): void
    {
        $query->addSelect([
            'successful_payment_amount' => Payment::query()
                ->select('amount')
                ->successfulMatchingContractUuidColumn('contracts.uuid')
                ->orderByDesc('id')
                ->limit(1),
        ]);
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