<?php

namespace App\Services\Admin\Analytics;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\ReceivedContract;
use App\Models\RefundableContract;
use Illuminate\Support\Facades\DB;

class EmployeeAnalyticsService
{
    public function getEmployeeCount(): int
    {
        return Employee::query()->count();
    }

    public function getRefundableSum(): float
    {
        return (float) RefundableContract::query()->sum('refund_amount');
    }

    public function countEmployeesWithReceivedContracts(): int
    {
        return Employee::query()
            ->has('receivedContract')
            ->count();
    }

    public function countEmployeesWithDocumentedOrders(): int
    {
        return Employee::query()
            ->whereHas('receivedContract.contract', fn ($q) => $q->where('is_completed', 1)->where('is_delete', 0))
            ->count();
    }

    public function countEmployeesWithUnpaidOrders(): int
    {
        $paidUuids = Payment::query()->where('status', 'success')->pluck('contract_uuid');

        return Employee::query()
            ->whereHas('receivedContract', function ($q) use ($paidUuids) {
                $q->whereHas('contract', function ($c) use ($paidUuids) {
                    if ($paidUuids->isNotEmpty()) {
                        $c->whereNotIn('uuid', $paidUuids);
                    }
                });
            })
            ->count();
    }

    public function getTopEmployeesByReceivedContracts(int $limit = 10): array
    {
        return Employee::query()
            ->withCount('receivedContract')
            ->having('received_contract_count', '>', 0)
            ->orderByDesc('received_contract_count')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'count' => $e->received_contract_count,
            ])
            ->values()
            ->all();
    }

    public function getTopEmployeesByConfirmedContracts(int $limit = 10): array
    {
        return Employee::query()
            ->withCount(['receivedContract as confirmed_count' => function ($q) {
                $q->whereHas('contract', fn ($c) => $c->where('is_completed', 1)->where('is_delete', 0));
            }])
            ->having('confirmed_count', '>', 0)
            ->orderByDesc('confirmed_count')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'count' => $e->confirmed_count,
            ])
            ->values()
            ->all();
    }

    public function getTopEmployeesByRefundSubmissions(int $limit = 10): array
    {
        return Employee::query()
            ->select('employees.id', 'employees.name')
            ->selectRaw('COUNT(refundable_contracts.id) as refunds_count')
            ->selectRaw('COALESCE(SUM(refundable_contracts.refund_amount), 0) as total_refunds')
            ->join('refundable_contracts', 'employees.id', '=', 'refundable_contracts.employee_id')
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('refunds_count')
            ->orderByDesc('total_refunds')
            ->limit($limit)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'count' => (int) $e->refunds_count,
                'total_refunds' => (float) $e->total_refunds,
            ])
            ->values()
            ->all();
    }

    public function getTopEmployeesByUnpaidOrders(int $limit = 10): array
    {
        $paidUuids = Payment::query()->where('status', 'success')->pluck('contract_uuid')->all();

        $rows = ReceivedContract::query()
            ->select('employee_id', DB::raw('COUNT(*) as unpaid_count'))
            ->whereHas('contract', function ($q) use ($paidUuids) {
                if ($paidUuids !== []) {
                    $q->whereNotIn('uuid', $paidUuids);
                }
            })
            ->groupBy('employee_id')
            ->orderByDesc('unpaid_count')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $employees = Employee::query()->whereIn('id', $rows->pluck('employee_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($employees) {
            $employee = $employees->get($row->employee_id);

            return [
                'id' => $row->employee_id,
                'name' => $employee?->name,
                'count' => (int) $row->unpaid_count,
            ];
        })->filter(fn ($item) => $item['name'] !== null)->values()->all();
    }
}
