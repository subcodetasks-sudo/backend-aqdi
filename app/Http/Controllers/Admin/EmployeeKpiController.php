<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Admin\EmployeeKpiService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;

class EmployeeKpiController extends Controller
{
    use Responser;

    public function __construct(
        protected EmployeeKpiService $kpis
    ) {}

    public function index(Request $request)
    {
        try {
            $period = $this->kpis->normalizePeriod($request->query('period'));
            $viewerId = $request->user() instanceof Employee ? (int) $request->user()->id : null;

            return $this->apiResponse([
                'periods' => $this->kpis->periodTabs($period),
                'period' => $period,
                'items' => $this->kpis->forAllEmployees($period, $viewerId),
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $employee = $request->user();
            if (! $employee instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            return $this->showPayload($request, $employee);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $employee = Employee::query()->with('roleRelation')->findOrFail($id);

            return $this->showPayload($request, $employee);
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function details(Request $request, int $id)
    {
        return $this->show($request, $id);
    }

    private function showPayload(Request $request, Employee $employee)
    {
        $employee->loadMissing('roleRelation');
        $period = $this->kpis->normalizePeriod($request->query('period'));
        $viewerId = $request->user() instanceof Employee ? (int) $request->user()->id : null;
        $detail = $this->kpis->forEmployee($employee, $period, $viewerId, true);

        return $this->apiResponse(array_merge([
            'periods' => $this->kpis->periodTabs($period),
        ], $detail), trans('api.success'));
    }
}
