<?php

namespace App\Services\Admin\Analytics;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\ReceivedContract;
use App\Models\RefundableContract;
use Illuminate\Support\Facades\DB;

class EmployeeAnalyticsService
{
    public function getTopEmployeesByReceivedContracts(): array
    {
        return Employee::withCount('receivedContract')
            ->having('received_contract_count', '>', 0)
            ->orderByDesc('received_contract_count')
            ->limit(10)
            ->pluck('name')
            ->values()
            ->all();
    }

    public function getTopEmployeesByConfirmedContracts(): array
    {
        return Employee::withCount(['receivedContract as confirmed_count' => function ($q) {
            $q->whereHas('contract', fn($c) => $c->where('is_completed', 1)->where('is_delete', 0));
        }])
            ->having('confirmed_count', '>', 0)
            ->orderByDesc('confirmed_count')
            ->limit(10)
            ->pluck('name')
            ->values()
            ->all();
    }

    public function getTopEmployeesByUnpaidOrders(): array
    {
        $paidUuids = Payment::where('status', 'success')->pluck('contract_uuid')->all();
        $employeeIds = ReceivedContract::whereHas('contract', function ($q) use ($paidUuids) {
            if (!empty($paidUuids)) {
                $q->whereNotIn('uuid', $paidUuids);
            }
        })
            ->select('employee_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('employee_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('employee_id')
            ->values();
        if ($employeeIds->isEmpty()) {
            return [];
        }
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        return $employeeIds->map(fn($id) => $employees[$id]->name ?? null)->filter()->values()->all();
    }

    public function getRefundableSum(): float
    {
        return RefundableContract::sum('refund_amount');
    }

    public function getEmployeeCount(): int
    {
        return Employee::count();
    }
}
