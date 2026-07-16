<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRefundableContractRequest;
use App\Http\Requests\Admin\UpdateRefundableContractApprovalRequest;
use App\Http\Resources\Admin\V2\Api\RefundableContractListResource;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Admin\RefundableContractService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class RefundableContractController extends Controller
{
    use Responser;

    public function __construct(
        protected RefundableContractService $refundableService
    ) {}

    /**
     * List refund requests (مسترجع).
     * GET /api/admin/refundable-contracts?period=today
     * GET /api/admin/analytics/refunds/contracts?period=today
     */
    public function index(Request $request)
    {
        try {
            $period = $this->refundableService->resolvePeriod($request->query('period'));
            $summary = $this->refundableService->buildIndexSummary($period);

            $query = $this->refundableService->periodQuery($period);

            if ($request->filled('admin_confirmed')) {
                $query->where('refundable_contracts.admin_confirmed', $request->boolean('admin_confirmed'));
            }

            if ($request->filled('contract_status_id')) {
                $query->whereHas('contract', fn ($q) =>
                    $q->where('contract_status_id', (int) $request->contract_status_id)
                );
            }

            $records = $query
                ->latest('refundable_contracts.created_at')
                ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

            return $this->apiResponse([
                'period' => $period,
                'label_ar' => $this->periodLabelAr($period),
                'summary' => $summary,
                'management_approval' => $summary['management_approval'],
                'contract_statuses' => $summary['contract_statuses'],
                'contracts' => RefundableContractListResource::collection($records),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                ],
            ], trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Submit refund request (طلب إسترجاع) — employee token.
     *
     * POST /api/admin/refundable-contracts
     * Also sets contract_status_id to مسترجع (2).
     * Body: { "contract_id": 42, "refund_amount": 500, "notes": null }
     * Or:   { "draft_contract_number": "000042", "refund_amount": 500 }
     */
    public function store(StoreRefundableContractRequest $request)
    {
        try {
            if (! $request->user() instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            /** @var Employee $employee */
            $employee = $request->user();

            $record = $this->refundableService->createRefundRequest(
                $employee,
                $request->validated()
            );

            return $this->apiResponse(
                (new RefundableContractListResource($record))->resolve(),
                trans('api.refund_request_created')
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Management approval (موافقة الإدارة): accept or reject.
     * When approved, sets is_refunded (تم الاسترجاع).
     *
     * POST|PUT|PATCH /api/admin/refundable-contracts/{uuid}
     * POST|PUT|PATCH /api/admin/analytics/refunds/contracts/{uuid}
     * Accept: { "admin_confirmed": true }
     * Reject: { "admin_confirmed": false }
     * Optional: { "refund_amount": 125.99, "notes": "..." }
     *
     * {uuid} = contract uuid first, then contract_id
     */
    public function update(UpdateRefundableContractApprovalRequest $request, string $uuid)
    {
        return $this->applyApproval($request, $uuid);
    }

    /**
     * Same as update, but uuid/id in body — use when hosting WAF blocks POST .../{uuid}.
     *
     * POST /api/admin/analytics/refunds/contracts/confirm
     * Body: { "uuid": "...", "admin_confirmed": true }
     *   or: { "contract_id": 40892, "admin_confirmed": true }
     *   or: { "id": 40892, "admin_confirmed": true }
     */
    public function confirm(UpdateRefundableContractApprovalRequest $request)
    {
        $key = $request->input('uuid')
            ?? $request->input('contract_id')
            ?? $request->input('id');

        if ($key === null || $key === '') {
            return $this->errorMessage(trans('api.refund_contract_id_required'), 422);
        }

        return $this->applyApproval($request, (string) $key);
    }

    private function applyApproval(UpdateRefundableContractApprovalRequest $request, string $key)
    {
        try {
            $record = $this->refundableService->findForAdmin($key);

            if (! $record) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $record = $this->refundableService->applyAdminUpdate($record, $request->validated());

            return $this->apiResponse(
                (new RefundableContractListResource($record))->resolve(),
                trans('api.updated_successfully')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/refundable-contracts/{uuid}
     * GET /api/admin/analytics/refunds/contracts/{uuid}
     *
     * {uuid} = contract uuid first, then contract_id
     */
    public function show(string $uuid)
    {
        try {
            $record = $this->refundableService->findForAdmin($uuid);

            if (! $record) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            return $this->apiResponse(
                (new RefundableContractListResource($record))->resolve(),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function periodLabelAr(string $period): string
    {
        return match ($period) {
            'today' => 'مسترجع اليوم',
            'week' => 'مسترجع الأسبوع',
            'month' => 'مسترجع الشهر',
            'year' => 'مسترجع العام',
            'total' => 'إجمالي المسترجع',
        };
    }
}
