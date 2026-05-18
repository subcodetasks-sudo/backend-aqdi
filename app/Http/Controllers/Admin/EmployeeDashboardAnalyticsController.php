<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAnalyticsMetric;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\Analytics\EmployeeAnalyticsService;
use Illuminate\Http\Request;
use Throwable;

class EmployeeDashboardAnalyticsController extends Controller
{
    use ResolvesAnalyticsMetric;
    use Responser;

    private const METRICS_CONFIG = 'employee_analytics_metrics';

    public function __construct(
        protected EmployeeAnalyticsService $employeeAnalytics
    ) {}

    public function mostReceivedOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $items = $this->employeeAnalytics->getTopEmployeesByReceivedContracts($limit);

            return $this->analyticsMetric(
                'most_received_orders_employee',
                $this->topEmployeeDisplayValue($items) ?? $this->employeeAnalytics->countEmployeesWithReceivedContracts(),
                $items,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostReturns(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $items = $this->employeeAnalytics->getTopEmployeesByRefundSubmissions($limit);

            return $this->analyticsMetric(
                'most_returns_employee',
                $this->employeeAnalytics->getRefundableSum(),
                $items,
                [
                    'top_employee_name' => $this->topEmployeeDisplayValue($items),
                ],
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostDocumentedOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $items = $this->employeeAnalytics->getTopEmployeesByConfirmedContracts($limit);

            return $this->analyticsMetric(
                'most_documented_orders_employee',
                $this->topEmployeeDisplayValue($items) ?? $this->employeeAnalytics->countEmployeesWithDocumentedOrders(),
                $items,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function totalCount()
    {
        try {
            return $this->analyticsMetric(
                'total_employees_count',
                $this->employeeAnalytics->getEmployeeCount(),
                [],
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostUnpaidOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $items = $this->employeeAnalytics->getTopEmployeesByUnpaidOrders($limit);

            return $this->analyticsMetric(
                'most_unpaid_orders_employee',
                $this->topEmployeeDisplayValue($items) ?? $this->employeeAnalytics->countEmployeesWithUnpaidOrders(),
                $items,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
