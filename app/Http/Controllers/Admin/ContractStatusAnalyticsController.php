<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\AnalyticsPeriodMetricResource;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Traits\Responser;
use App\Services\Admin\RefundableContractService;
use App\Services\Admin\Analytics\ContractStatusAnalyticsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ContractStatusAnalyticsController extends Controller
{
    use Responser;

    private const METRICS_CONFIG = 'contract_status_analytics_metrics';

    public function __construct(
        protected ContractStatusAnalyticsService $statusAnalytics
    ) {}

    /**
     * All period cards (today, week, month, year, total) for one status.
     * GET /api/admin/analytics/contract-status/2
     */
    public function summary(Request $request, int $contractStatusId)
    {
        try {
            $status = $this->statusAnalytics->resolveStatus($contractStatusId);
            $metrics = $this->statusAnalytics->getPeriodMetrics($contractStatusId);
            $limit = min(max((int) $request->input('limit', 10), 1), 100);

            $cards = [];
            foreach (config(self::METRICS_CONFIG, []) as $key => $definition) {
                $period = $definition['period'];
                $periodData = $metrics[$period];

                $cards[] = $this->buildCardPayload(
                    $key,
                    $definition,
                    $status,
                    $periodData['value'],
                    $periodData['percentage_change'],
                    $this->statusAnalytics->getContractsForPeriod($contractStatusId, $period, $limit)
                );
            }

            return $this->apiResponse([
                'contract_status_id' => $status->id,
                'contract_status_name' => $status->name,
                'cards' => collect($cards)->map(
                    fn ($card) => (new AnalyticsPeriodMetricResource($card))->resolve()
                )->values(),
            ], trans('api.success'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function daily(Request $request, int $contractStatusId)
    {
        return $this->periodMetric($request, $contractStatusId, 'contract_status_daily');
    }

    public function weekly(Request $request, int $contractStatusId)
    {
        return $this->periodMetric($request, $contractStatusId, 'contract_status_weekly');
    }

    public function monthly(Request $request, int $contractStatusId)
    {
        return $this->periodMetric($request, $contractStatusId, 'contract_status_monthly');
    }

    public function yearly(Request $request, int $contractStatusId)
    {
        return $this->periodMetric($request, $contractStatusId, 'contract_status_yearly');
    }

    public function total(Request $request, int $contractStatusId)
    {
        return $this->periodMetric($request, $contractStatusId, 'contract_status_total');
    }

    /**
     * Paginated contracts for a status (e.g. status 3 = مكتمل).
     * GET /api/admin/analytics/contract-status/3/contracts?period=today
     */
    public function contracts(Request $request, int $contractStatusId)
    {
        try {
            $status = $this->statusAnalytics->resolveStatus($contractStatusId);
            $period = $request->query('period');
            if ($period !== null && $period !== '' && ! in_array($period, RefundableContractService::PERIODS, true)) {
                return $this->errorMessage(
                    'period must be one of: '.implode(', ', RefundableContractService::PERIODS),
                    422
                );
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $contracts = $this->statusAnalytics->paginateContracts(
                $contractStatusId,
                $period ?: null,
                $perPage
            );

            return $this->apiResponse([
                'contract_status_id' => $status->id,
                'contract_status_name' => $status->name,
                'period' => $period,
                'contracts' => OrderResource::collection($contracts),
                'pagination' => [
                    'current_page' => $contracts->currentPage(),
                    'last_page' => $contracts->lastPage(),
                    'per_page' => $contracts->perPage(),
                    'total' => $contracts->total(),
                ],
            ], trans('api.success'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function periodMetric(Request $request, int $contractStatusId, string $metricKey)
    {
        try {
            $definition = config(self::METRICS_CONFIG.'.'.$metricKey);
            if (! is_array($definition)) {
                throw new InvalidArgumentException("Unknown metric key: {$metricKey}");
            }

            $status = $this->statusAnalytics->resolveStatus($contractStatusId);
            $metrics = $this->statusAnalytics->getPeriodMetrics($contractStatusId);
            $period = $definition['period'];
            $periodData = $metrics[$period];
            $limit = min(max((int) $request->input('limit', 10), 1), 100);

            $payload = $this->buildCardPayload(
                $metricKey,
                $definition,
                $status,
                $periodData['value'],
                $periodData['percentage_change'],
                $this->statusAnalytics->getContractsForPeriod($contractStatusId, $period, $limit)
            );

            return $this->apiResponse(
                (new AnalyticsPeriodMetricResource($payload))->resolve(),
                trans('api.success')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  \Illuminate\Support\Collection<int, \App\Models\Contract>|\Illuminate\Contracts\Pagination\LengthAwarePaginator  $contracts
     * @return array<string, mixed>
     */
    private function buildCardPayload(
        string $key,
        array $definition,
        $status,
        int $value,
        ?float $percentageChange,
        $contracts
    ): array {
        $statusName = $status->name;
        $orders = OrderResource::collection($contracts)->resolve();

        return [
            'key' => $key,
            'label_ar' => "عقود {$statusName} — {$definition['label_ar']}",
            'label_en' => "Contracts ({$statusName}) — ".($definition['label_en'] ?? $definition['label_ar']),
            'value' => $value,
            'type' => $definition['type'],
            'percentage_change' => $percentageChange,
            'contracts' => $orders,
            'items' => $orders,
            'meta' => [
                'contract_status_id' => $status->id,
                'contract_status_name' => $statusName,
                'period' => $definition['period'],
            ],
        ];
    }
}
