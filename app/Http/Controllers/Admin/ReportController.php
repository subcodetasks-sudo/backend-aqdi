<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Admin\ReportsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class ReportController extends Controller
{
    use Responser;

    public function __construct(
        protected ReportsService $reports
    ) {}

    public function orders(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge(
                $this->periodMeta($filter),
                $this->reports->orders($filter, $this->contractType($request), $this->employeeId($request))
            ), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function sales(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge(
                $this->periodMeta($filter),
                $this->reports->sales($filter, $this->contractType($request), $this->employeeId($request))
            ), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function profits(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge(
                $this->periodMeta($filter),
                $this->reports->profits($filter, $this->canSeeSalaries($request))
            ), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function profitSettingsShow(Request $request)
    {
        try {
            return $this->apiResponse(
                $this->reports->profitSettings($this->canSeeSalaries($request)),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function profitSettingsUpdate(Request $request)
    {
        try {
            $data = $request->validate([
                'moyasar_fee_percent' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
                'monthly_salaries' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'operating_budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'marketing_budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            ]);

            return $this->apiResponse(
                $this->reports->updateProfitSettings($data, $this->canEditSalaries($request)),
                trans('api.updated_successfully')
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function customers(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge(
                $this->periodMeta($filter),
                $this->reports->customers($filter)
            ), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function performance(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge(
                $this->periodMeta($filter),
                $this->reports->performance($filter)
            ), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @param  array{key: string, date_from: string|null, date_to: string|null}  $filter
     * @return array<string, mixed>
     */
    private function periodMeta(array $filter): array
    {
        return [
            'periods' => $this->reports->reportPeriodTabs($filter['key']),
            'period' => $filter['key'],
            'date_from' => $filter['date_from'],
            'date_to' => $filter['date_to'],
        ];
    }

    private function contractType(Request $request): ?string
    {
        $type = $request->query('contract_type');

        return in_array($type, ['residential', 'housing', 'commercial'], true)
            ? ($type === 'residential' ? 'housing' : $type)
            : null;
    }

    private function employeeId(Request $request): ?int
    {
        return $request->filled('employee_id') && is_numeric($request->query('employee_id'))
            ? (int) $request->query('employee_id')
            : null;
    }

    private function canSeeSalaries(Request $request): bool
    {
        $employee = $request->user();

        return $employee instanceof Employee && $employee->hasPermission('employee_salaries.view');
    }

    private function canEditSalaries(Request $request): bool
    {
        $employee = $request->user();

        return $employee instanceof Employee && $employee->hasPermission('employee_salaries.edit');
    }
}
