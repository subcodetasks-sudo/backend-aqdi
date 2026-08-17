<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContractRequest;
use App\Http\Requests\Admin\UpdateReturnContractAcceptanceRequest;
use App\Http\Resources\Admin\V2\Api\AdminContractDetailResource;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\DraftContractStatus;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\TenantRole;
use App\Services\FirebaseNotificationService;
use App\Services\ContractStatusHistoryService;
use App\Services\ContractStatusCaseService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use Responser;

    /**
     * Single orders list. Filter with query params — do not use a second list URL.
     *
     * GET /api/admin/orders
     *   status_id=1                 contract status (also: status, contract_status_id)
     *   is_draft=1                  draft contracts; status_id then filters draft_contract_statuses
     *   complete=1 | incomplete=1   payment completion
     *   is_received=0|1             awaiting receipt / already received
     *   return=1                    return-orders list
     *   return_status=pending|accept|reject
     *   list=draft|return|received|completed-draft
     *   search, contract_type, user_id, page, per_page
     */
    public function orders(Request $request)
    {
        if ($this->wantsReturnList($request)) {
            return $this->returnOrders($request);
        }

        if ($this->wantsDraftList($request)) {
            return $this->draftContracts($request);
        }

        if ($this->wantsCompletedDraftList($request)) {
            return $this->completedAndDraft($request);
        }

        $statusId = $this->resolveContractStatusIdFromRequest($request);
        $isCompleted = $this->resolveCompletionFilterFromRequest($request);
        $receivedPresenceFilter = $this->parseReceivedContractQueryFilter($request);

        if ($statusId !== null) {
            $request->merge(['status_id' => $statusId]);
            $this->validate($request, [
                'status_id' => 'required|integer|exists:contract_statuses,id',
            ]);

            $orders = Contract::query()
                ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
                ->notDeleted()
                ->reachedAdminOrderStep()
                ->where('contract_status_id', $statusId)
                ->when($isCompleted !== null, fn ($q) =>
                    $q->where('is_completed', $isCompleted ? 1 : 0)
                )
                ->when($request->filled('search'), fn ($q) =>
                    $q->adminSearch($request->string('search')->toString())
                )
                ->when($request->filled('contract_type'), fn ($q) =>
                    $q->where('contract_type', $request->contract_type)
                )
                ->when($request->filled('user_id'), fn ($q) =>
                    $q->where('user_id', $request->user_id)
                )
                ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
                ->with($this->orderListRelations())
                ->latest()
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $orders,
                OrderResource::collection($orders),
                trans('api.success'),
                array_merge(
                    [
                        'contract_status_id' => $statusId,
                        'status_id' => $statusId,
                        'is_completed' => $isCompleted,
                    ],
                    $this->newOrdersListSummaryIfNeeded($request, $statusId)
                )
            );
        }

        if ($isCompleted !== null) {
            $orders = Contract::query()
                ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
                ->notDeleted()
                ->reachedAdminOrderStep()
                ->where('is_completed', $isCompleted ? 1 : 0)
                ->when($request->filled('search'), fn ($q) =>
                    $q->adminSearch($request->string('search')->toString())
                )
                ->when($request->filled('contract_type'), fn ($q) =>
                    $q->where('contract_type', $request->contract_type)
                )
                ->when($request->filled('user_id'), fn ($q) =>
                    $q->where('user_id', $request->user_id)
                )
                ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
                ->with($this->orderListRelations())
                ->latest()
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $orders,
                OrderResource::collection($orders),
                trans('api.success'),
                [
                    'is_completed' => $isCompleted ? 1 : 0,
                ]
            );
        }

        if ($receivedPresenceFilter === false) {
            $orders = $this->awaitingReceiptOrdersQuery($request)
                ->paginate($this->perPageFromRequest($request, 120, 200));

            return $this->paginatedApiResponse(
                $orders,
                OrderResource::collection($orders),
                trans('api.success'),
                array_merge(
                    [
                        'contract_status_id' => ContractStatus::NEW_ID,
                        'is_received' => false,
                    ],
                    $this->newOrdersListSummary($request)
                )
            );
        }

        if ($receivedPresenceFilter === true || strtolower(trim((string) $request->input('list', ''))) === 'received') {
            return $this->receivedOrders($request);
        }

        $hasExplicitStatusFilter = $request->filled('status_name');
        $defaultStatusId = ContractStatus::RECEIVED_ID;

        $orders = Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->reachedAdminOrderStep()
            ->tap(fn ($q) => $this->applyContractStatusFiltersToQuery($q, $request))
            ->when(! $hasExplicitStatusFilter, fn ($q) =>
                $q->where('contract_status_id', $defaultStatusId)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->tap(fn ($q) => $this->applyReceivedContractPresenceToQuery($q, $request))
            ->with($this->orderListRelations())
            ->latest()
            ->paginate($this->perPageFromRequest($request, 120, 200));

        return $this->paginatedApiResponse(
            $orders,
            OrderResource::collection($orders),
            trans('api.success'),
            [
                'contract_status_id' => $hasExplicitStatusFilter ? null : $defaultStatusId,
                'is_received' => $receivedPresenceFilter,
            ]
        );
    }

    /**
     * New contracts (status = 1) not yet recorded in `received_contracts`.
     */
    private function awaitingReceiptOrdersQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->where('contract_status_id', ContractStatus::NEW_ID)
            ->whereDoesntHave('receivedContract')
            ->when($request->has('is_completed'), fn ($q) =>
                $q->where('is_completed', $request->boolean('is_completed') ? 1 : 0)
            )
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
            ->latest();
    }

    /**
     * @return array<string, mixed>
     */
    private function newOrdersListSummaryIfNeeded(Request $request, int $statusId): array
    {
        if ($statusId !== ContractStatus::NEW_ID) {
            return [];
        }

        return $this->newOrdersListSummary($request);
    }

    /**
     * Dashboard cards for new orders (status = 1): total / waiting > 15 min / waiting > 30 min.
     *
     * @return array{summary: array<string, mixed>}
     */
    private function newOrdersListSummary(Request $request): array
    {
        $now = now();
        $over15 = $now->copy()->subMinutes(15);
        $over30 = $now->copy()->subMinutes(30);

        $isCompleted = $this->resolveCompletionFilterFromRequest($request);

        $base = Contract::query()
            ->notDeleted()
            ->reachedAdminOrderStep()
            ->where('contract_status_id', ContractStatus::NEW_ID)
            ->when($isCompleted !== null, fn ($q) =>
                $q->where('is_completed', $isCompleted ? 1 : 0)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->adminSearch($request->string('search')->toString())
            )
            ->when($request->filled('contract_type'), fn ($q) =>
                $q->where('contract_type', $request->contract_type)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            );

        $total = (clone $base)->count();
        $exceeded15 = (clone $base)->where('created_at', '<=', $over15)->count();
        $exceeded30 = (clone $base)->where('created_at', '<=', $over30)->count();

        return [
            'summary' => [
                'total_new_orders' => $total,
                'total_new_orders_label' => 'إجمالي الطلبات الجديدة',
                'exceeded_15_minutes' => $exceeded15,
                'exceeded_15_minutes_label' => 'تجاوزت 15 دقيقة',
                'exceeded_30_minutes' => $exceeded30,
                'exceeded_30_minutes_label' => 'تجاوزت 30 دقيقة',
                'cards' => [
                    [
                        'key' => 'total_new_orders',
                        'label' => 'إجمالي الطلبات الجديدة',
                        'count' => $total,
                    ],
                    [
                        'key' => 'exceeded_15_minutes',
                        'label' => 'تجاوزت 15 دقيقة',
                        'count' => $exceeded15,
                    ],
                    [
                        'key' => 'exceeded_30_minutes',
                        'label' => 'تجاوزت 30 دقيقة',
                        'count' => $exceeded30,
                    ],
                ],
            ],
        ];
    }

    /**
     * Returned contracts list — received contracts only (contract_status_id = 6 مستلم).
     * GET /api/admin/orders/return
     *
     * Filter: return_status=pending|accept|reject
     * Aliases: accept_retrun_status, acceptance_status
     */
    public function returnOrders(Request $request)
    {
        try {
            $returnStatus = $this->resolveReturnAcceptanceFilter($request);

            $contracts = $this->returnContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                [
                    'contract_status_id' => ContractStatus::RECEIVED_ID,
                    'return_status' => $returnStatus,
                    'return_status_filters' => ['pending', 'accept', 'reject'],
                ]
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
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
                ->reachedAdminOrderStep()
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
     * Alias of GET /orders?status_id={statusId}
     */
    public function byStatus(Request $request, $statusId)
    {
        $request->merge(['status_id' => $statusId]);

        return $this->orders($request);
    }

    /**
     * Alias of GET /orders?is_draft=1&status_id={statusId}
     */
    public function draftByStatus(Request $request, $statusId)
    {
        $request->merge([
            'is_draft' => 1,
            'status_id' => $statusId,
            'draft_contract_status_id' => $statusId,
        ]);

        return $this->orders($request);
    }

    /**
     * Alias of GET /orders?incomplete=1
     */
    public function incomplete(Request $request)
    {
        $request->merge(['incomplete' => 1]);

        return $this->orders($request);
    }

    /**
     * Draft contracts (is_draft = true).
     * GET /api/admin/orders/draft
     * Filter: status_id | draft_contract_status_id → draft_contract_statuses.id
     */
    public function draftContracts(Request $request)
    {
        try {
            if ($request->filled('status_id') || $request->filled('draft_contract_status_id')) {
                $statusId = (int) ($request->input('draft_contract_status_id') ?? $request->input('status_id'));
                $request->merge(['status_id' => $statusId]);
                $this->validate($request, [
                    'status_id' => 'required|integer|exists:draft_contract_statuses,id',
                ]);
            }

            $contracts = $this->draftContractsQuery($request)
                ->with($this->orderListRelations())
                ->paginate($this->perPageFromRequest($request, 120, 200));

            $meta = ['is_draft' => true];
            if ($request->filled('draft_contract_status_id') || $request->filled('status_id')) {
                $meta['draft_contract_status_id'] = (int) ($request->input('draft_contract_status_id') ?? $request->input('status_id'));
            }

            return $this->paginatedApiResponse(
                $contracts,
                OrderResource::collection($contracts),
                trans('api.success'),
                $meta
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
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
            ->reachedAdminOrderStep()
            ->where('contract_status_id', ContractStatus::RECEIVED_ID)
            ->whereHas('receivedContract')
            ->tap(fn ($q) => $this->applyReturnAcceptanceFilterToQuery($q, $request))
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
     * pending = لم يُبت فيها بعد (employee_id null)
     * accept  = accept_retrun_contract = true
     * reject  = رُفضت (false + employee_id موجود)
     */
    private function applyReturnAcceptanceFilterToQuery($query, Request $request): void
    {
        $status = $this->resolveReturnAcceptanceFilter($request);
        if ($status === null) {
            return;
        }

        match ($status) {
            'pending' => $query->whereNull('accept_retrun_contract_employee_id'),
            'accept' => $query->where('accept_retrun_contract', true),
            'reject' => $query
                ->where('accept_retrun_contract', false)
                ->whereNotNull('accept_retrun_contract_employee_id'),
        };
    }

    /**
     * @return 'pending'|'accept'|'reject'|null
     */
    private function resolveReturnAcceptanceFilter(Request $request): ?string
    {
        $raw = $request->input('return_status')
            ?? $request->input('accept_retrun_status')
            ?? $request->input('acceptance_status');

        if ($raw === null || $raw === '') {
            return null;
        }

        $status = strtolower(trim((string) $raw));

        // aliases
        $status = match ($status) {
            'accepted', 'approve', 'approved', '1', 'true' => 'accept',
            'rejected', 'reject', '0', 'false' => 'reject',
            'pending', 'wait', 'waiting' => 'pending',
            default => $status,
        };

        if (! in_array($status, ['pending', 'accept', 'reject'], true)) {
            throw new InvalidArgumentException(
                'return_status يجب أن يكون: pending أو accept أو reject'
            );
        }

        return $status;
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
            ->reachedAdminOrderStep()
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
        $draftStatusId = null;
        if ($request->filled('draft_contract_status_id')) {
            $draftStatusId = (int) $request->input('draft_contract_status_id');
        } elseif ($request->filled('status_id')) {
            $draftStatusId = (int) $request->input('status_id');
        }

        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->reachedAdminOrderStep()
            ->draft()
            ->tap(fn ($q) => $this->applyDraftStatusIdFilter($q, $draftStatusId))
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
     * Filter drafts by draft_contract_statuses.id (strict).
     */
    private function applyDraftStatusIdFilter($query, ?int $draftStatusId): void
    {
        if ($draftStatusId === null) {
            return;
        }

        $query->where('draft_contract_status_id', $draftStatusId);
    }

    private function completedAndDraftContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->reachedAdminOrderStep()
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
            'units.unitType',
            'units.unitUsage',
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
            'comments.employee',
            'invoices',
        ];
    }



     public function complete(Request $request)
    {
        $request->merge(['complete' => 1]);

        return $this->orders($request);
    }

    private function completeContractsQuery(Request $request)
    {
        return Contract::query()
            ->tap(fn ($q) => $this->applySuccessfulPaymentAmountSelect($q))
            ->notDeleted()
            ->reachedAdminOrderStep()
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
     * Resolve numeric contract status id from query:
     * status_id | contract_status_id | status (when numeric).
     */
    private function resolveContractStatusIdFromRequest(Request $request): ?int
    {
        foreach (['status_id', 'contract_status_id', 'status'] as $key) {
            if (! $request->filled($key)) {
                continue;
            }

            $value = $request->input($key);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function listQueryName(Request $request): string
    {
        return strtolower(trim((string) $request->input('list', '')));
    }

    private function wantsReturnList(Request $request): bool
    {
        return $this->listQueryName($request) === 'return' || $request->boolean('return');
    }

    private function wantsDraftList(Request $request): bool
    {
        return $this->listQueryName($request) === 'draft' || $request->boolean('is_draft');
    }

    private function wantsCompletedDraftList(Request $request): bool
    {
        return $this->listQueryName($request) === 'completed-draft'
            || $request->boolean('completed_draft');
    }

    /**
     * Resolve complete / incomplete query filter.
     * true = completed, false = incomplete, null = not requested.
     *
     * Params: complete, incomplete, is_completed, status=complete|incomplete
     */
    private function resolveCompletionFilterFromRequest(Request $request): ?bool
    {
        if ($request->filled('incomplete') && $this->isTruthyQueryFlag($request->input('incomplete'))) {
            return false;
        }

        if ($request->filled('complete') && $this->isTruthyQueryFlag($request->input('complete'))) {
            return true;
        }

        if ($request->has('is_completed') && $request->query('is_completed') !== null && $request->query('is_completed') !== '') {
            return $request->boolean('is_completed');
        }

        $status = strtolower(trim((string) $request->input('status', '')));
        if (in_array($status, ['complete', 'completed'], true)) {
            return true;
        }
        if (in_array($status, ['incomplete', 'uncompleted', 'not_completed'], true)) {
            return false;
        }

        return null;
    }

    private function isTruthyQueryFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Shared filters: status, status_id, status_name, contract_status_id, is_completed.
     */
    private function applyContractStatusFiltersToQuery($query, Request $request): void
    {
        $status = $request->get('status');
        $statusId = $this->resolveContractStatusIdFromRequest($request);

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
            ->when($statusId !== null, fn ($q) =>
                $q->where('contract_status_id', $statusId)
            )
            ->when($request->filled('status_name'), fn ($q) =>
                $q->whereHas('contractStatus', fn ($sq) =>
                    $sq->where('name', 'like', '%'.$request->status_name.'%')
                )
            )
            ->when($request->filled('contract_status_id'), fn ($q) =>
                $q->where('contract_status_id', $request->contract_status_id)
            );
    }

    public function show(Request $request, $id)
    {
        $contract = $this->findAdminContract((int) $id);

        return $this->apiResponse(
            $this->fullAdminContractPayload($contract, $request),
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
            ->reachedAdminOrderStep()
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
     * Single admin contract payload: all columns, relations, comments, invoice, timeline, and step groups.
     *
     * @return array<string, mixed>
     */
    private function fullAdminContractPayload(Contract $contract, Request $request): array
    {
        $contract->load($this->contractDetailRelations());
        $detail = (new AdminContractDetailResource($contract))->toArray($request);

        return array_merge(
            $detail,
            $this->buildStepBasedDetailResponse($detail),
            [
                'user_contracts' => $this->userContractSummariesForUser($contract->user_id),
            ]
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
                'is_real',
                'real_id',
                'real_units_id',
                'image_instrument',
                'image_instrument_from_the_back',
                'image_instrument_from_the_front',
                'is_multiple_trusteeship_deed_copy',
                'copy_of_the_endowment_registration_certificate',
                'copy_of_the_trusteeship_deed',
                'Image_inheritance_certificate',
                'copy_power_of_attorney_from_heirs_to_agent',
                'copy_of_guardians_power_of_attorney_for_agent',
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
                'property_owner_dob_day',
                'property_owner_dob_month',
                'property_owner_dob_year',
                'id_num_of_property_owner_agent',
                'property_owner_mobile',
                'add_legal_agent_of_owner',
                'type_dob_property_owner_agent',
                'dob_of_property_owner_agent',
                'dob_of_property_owner_agent_day',
                'dob_of_property_owner_agent_month',
                'dob_of_property_owner_agent_year',
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
                'return_status' => Arr::get($detail, 'return_status'),
                'return_contract' => (bool) Arr::get($detail, 'return_contract', false),
                'draft_contract_number' => Arr::get($detail, 'draft_contract_number'),
                'refund_amount' => Arr::get($detail, 'refund_amount'),
                'received_at' => Arr::get($detail, 'received_at'),
                'received_since' => Arr::get($detail, 'received_since'),
                'received_since_label_ar' => Arr::get($detail, 'received_since_label_ar'),
                'receive_speed' => Arr::get($detail, 'receive_speed'),
                'receive_speed_label_ar' => Arr::get($detail, 'receive_speed_label_ar'),
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
                'number_of_rooms',
                'The_number_of_halls',
                'number_of_councils',
                'The_number_of_kitchens',
                'The_number_of_the_toilet',
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
                'electricity_meter_ownership',
                'water_meter_ownership',
                'unit',
            ]), [
                'unit_type_name' => $this->relationName(Arr::get($detail, 'unit_type')),
                'unit_usage_name' => $this->relationName(Arr::get($detail, 'unit_usage')),
            ]),
            'step3' => array_merge(Arr::only($detail, [
               'tenant_name',
                'tenant_entity',
                'type_tenant_dob',
                'tenant_id_num',
                'tenant_dob',
                'tenant_dob_day',
                'tenant_dob_month',
                'tenant_dob_year',
                'tenant_mobile',
                'tenant_email',
                'tenant_nationality',
                'tenant_work',
                'tenant_gender',
                'tenant_entity_unified_registry_number',
                'authorization_type',
                'is_there_a_legal_representative_of_the_tenant',
                'id_num_of_property_tenant_agent',
                'type_dob_tenant_agent',
                'dob_of_property_tenant_agent',
                'dob_of_property_tenant_agent_day',
                'dob_of_property_tenant_agent_month',
                'dob_of_property_tenant_agent_year',
                'mobile_of_property_tenant_agent',
                'copy_of_the_owner_record',
                'notes',
                'tenant_role_id',
                'tenant_role',
            ]), [
                'tenant_role_names' => $this->tenantRoleNames($detail),
            ]),
            'step4' => array_merge(Arr::only($detail, [
                'contract_starting_date',
                'contract_starting_date_day',
                'contract_starting_date_month',
                'contract_starting_date_year',
                'type_contract_starting_date',
                'contract_term_in_years',
                'duration_preset',
                'duration_years',
                'duration_months',
                'total_months',
                'annual_rent_amount_for_the_unit',
                'payment_type_id',
                'additional_terms',
                'text_additional_terms',
                'notes_edits',
                'tenant_roles',
                'tenant_role_ids',
                'tenant_role_id',
                'tenant_role_values',
                'conditions',
                'other_conditions',
                'other_conditions_list',
                'other_conditions_count',
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

    /**
     * Single status update for contract or draft.
     * POST /api/admin/orders/{id}/status
     *
     * Body: status_id (required). Optional is_draft (auto from the contract).
     * Extra case fields: deed_type, deed_number, ejar_contract_number, notes,
     * attachment, ejar_contract_draft_number, contact_number_mode, contact_number.
     */
    public function updateStatus(Request $request, $id)
    {
        $statusId = $request->input('status_id')
            ?? $request->input('contract_status_id')
            ?? $request->input('draft_contract_status_id');

        if ($statusId === null || ! is_numeric($statusId)) {
            return $this->errorResponse([
                'status_id' => ['status_id مطلوب.'],
            ], 422);
        }

        try {
            $contract = $this->findAdminContract((int) $id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse(
                null,
                trans('api.contract_not_found'),
                false,
                404
            );
        }

        $isDraft = $request->exists('is_draft')
            ? $request->boolean('is_draft')
            : (bool) $contract->is_draft;

        if ($isDraft) {
            $request->merge([
                'draft_contract_status_id' => (int) $statusId,
                'status_id' => (int) $statusId,
            ]);

            return $this->updateDraftContractStatus($request, $id);
        }

        $request->merge([
            'contract_status_id' => (int) $statusId,
            'status_id' => (int) $statusId,
        ]);

        return $this->updateContractStatus($request, $id);
    }

    /**
     * POST /api/admin/orders/{id}/contract-status
     *
     * Extra fields by status:
     * - 9 توثيق العقد في إيجار: deed_type (paper|electronic|other), deed_number
     * - 10 بانتظار المشرف: ejar_contract_number, notes?
     * - 2 استرجاع: attachment? (file)
     * - 8 إرسال مسودة عبر واتساب: ejar_contract_draft_number, contact_number_mode (same|another), contact_number if another
     */
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
            $statusId = (int) $request->contract_status_id;
            $status = ContractStatus::query()->find($statusId);

            $caseError = $this->validateStatusCase($request, $contract, $statusId, $status?->name);
            if ($caseError !== null) {
                return $caseError;
            }

            $this->persistStatusChange($request, $contract, 'contract_status_id', $statusId, $status?->name);

            return $this->apiResponse(
                $this->fullAdminContractPayload($contract, $request),
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

            $statusId = (int) $request->draft_contract_status_id;
            $status = DraftContractStatus::query()->find($statusId);

            $caseError = $this->validateStatusCase($request, $contract, $statusId, $status?->name);
            if ($caseError !== null) {
                return $caseError;
            }

            $this->persistStatusChange($request, $contract, 'draft_contract_status_id', $statusId, $status?->name);

            return $this->apiResponse(
                $this->fullAdminContractPayload($contract, $request),
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
            $caseExtra = [];

            if (array_key_exists('contract_status_id', $payload)) {
                $statusId = (int) $payload['contract_status_id'];
                $status = ContractStatus::query()->find($statusId);
                $caseError = $this->validateStatusCase($request, $contract, $statusId, $status?->name);
                if ($caseError !== null) {
                    return $caseError;
                }
                $extracted = app(ContractStatusCaseService::class)->extract($request, $contract, $statusId, $status?->name);
                $caseExtra = array_merge($caseExtra, $extracted);
                $payload = array_merge($payload, $extracted);
            }

            if (array_key_exists('draft_contract_status_id', $payload)) {
                $statusId = (int) $payload['draft_contract_status_id'];
                $status = DraftContractStatus::query()->find($statusId);
                $caseError = $this->validateStatusCase($request, $contract, $statusId, $status?->name);
                if ($caseError !== null) {
                    return $caseError;
                }
                $extracted = app(ContractStatusCaseService::class)->extract($request, $contract, $statusId, $status?->name);
                $caseExtra = array_merge($caseExtra, $extracted);
                $payload = array_merge($payload, $extracted);
            }

            $statusFields = ['contract_status_id', 'draft_contract_status_id', 'draft_before_paid', 'draft_after_paid'];
            $statusChanged = false;
            foreach ($statusFields as $field) {
                if (array_key_exists($field, $payload)
                    && (string) ($contract->{$field} ?? '') !== (string) ($payload[$field] ?? '')
                ) {
                    $statusChanged = true;
                    break;
                }
            }

            $contract->fill($payload);
            $contract->save();
            $contract->refresh();

            if ($statusChanged) {
                try {
                    $contract->loadMissing(['contractStatus', 'draftContractStatus']);
                    $caseService = app(ContractStatusCaseService::class);
                    $statusId = (int) ($contract->is_draft
                        ? $contract->draft_contract_status_id
                        : $contract->contract_status_id);
                    $statusName = $contract->is_draft
                        ? $contract->draftContractStatus?->name
                        : $contract->contractStatus?->name;
                    app(ContractStatusHistoryService::class)->record($contract, [
                        'source' => 'admin',
                        'meta' => $caseService->historyMeta($statusId, $statusName, $caseExtra),
                    ]);
                    app(FirebaseNotificationService::class)->notifyContractStatusChanged($contract);
                } catch (\Throwable $notifyError) {
                    report($notifyError);
                }
            }

            return $this->apiResponse(
                $this->fullAdminContractPayload($contract, $request),
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

            if ((int) $contract->contract_status_id === ContractStatus::RETURN_ID) {
                return $this->errorMessage(trans('api.refund_contract_already_returned'), 422);
            }

            $contract->update([
                'accept_retrun_contract' => $accepted,
                'accept_retrun_contract_employee_id' => $employee->id,
            ]);

            return $this->apiResponse(
                $this->fullAdminContractPayload($contract, $request),
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

    /**
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function validateStatusCase(Request $request, Contract $contract, int $statusId, ?string $statusName)
    {
        $contract->loadMissing('user');

        $caseService = app(ContractStatusCaseService::class);
        $validator = Validator::make(
            $request->all(),
            $caseService->rules($statusId, $statusName),
            $caseService->messages()
        );
        $validator->after(function ($validator) use ($caseService, $request, $statusId, $statusName, $contract) {
            $caseService->afterValidation($validator, $request, $statusId, $statusName, $contract);
        });

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        return null;
    }

    private function persistStatusChange(
        Request $request,
        Contract $contract,
        string $statusColumn,
        int $statusId,
        ?string $statusName
    ): void {
        $caseService = app(ContractStatusCaseService::class);
        $extra = $caseService->extract($request, $contract, $statusId, $statusName);

        $contract->update(array_merge(
            [$statusColumn => $statusId],
            $extra
        ));
        $contract->load($this->contractDetailRelations());

        try {
            app(ContractStatusHistoryService::class)->record($contract, [
                'source' => 'admin',
                'meta' => $caseService->historyMeta($statusId, $statusName, $extra),
            ]);
            app(FirebaseNotificationService::class)->notifyContractStatusChanged($contract);
        } catch (\Throwable $notifyError) {
            report($notifyError);
        }
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
            if ($raw === null || $raw === '') {
                return false;
            }

            return $request->boolean('is_received');
        }

        if (! $request->has('received_contract')) {
            return null;
        }

        $raw = $request->query('received_contract');
        if ($raw === null || $raw === '') {
            return false;
        }

        return $request->boolean('received_contract');
    }


}