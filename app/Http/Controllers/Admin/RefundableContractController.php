<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\RefundableContractListResource;
use App\Http\Traits\Responser;
use App\Services\Admin\RefundableContractService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class RefundableContractController extends Controller
{
    use Responser;

    public function __construct(
        protected RefundableContractService $refundableService
    ) {}

    /**
     * List refund requests (مسترجع) — matches analytics table UI.
     * GET /api/admin/analytics/refunds/contracts?period=today
     */
    public function index(Request $request)
    {
        try {
            $period = $this->refundableService->resolvePeriod($request->query('period'));

            $query = $this->refundableService->baseQuery();
            $this->refundableService->applyPeriod($query, $period);

            if ($request->filled('admin_confirmed')) {
                $query->where('admin_confirmed', $request->boolean('admin_confirmed'));
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
     * GET /api/admin/analytics/refunds/contracts/{id}
     */
    public function show(int $id)
    {
        try {
            $record = $this->refundableService->baseQuery()->find($id);

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
