<?php

namespace App\Services\Admin;

use App\Models\RefundableContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class RefundableContractService
{
    public const PERIODS = ['today', 'week', 'month', 'year', 'total'];

    public function baseQuery(): Builder
    {
        return RefundableContract::query()
            ->with([
                'contract.user:id,fname,lname,mobile',
                'contract.contractStatus:id,name,color',
                'employee:id,name',
            ]);
    }

    public function applyPeriod(Builder $query, string $period): Builder
    {
        if (! in_array($period, self::PERIODS, true)) {
            throw new InvalidArgumentException("Unknown period: {$period}");
        }

        return match ($period) {
            'today' => $query->whereDate('refundable_contracts.created_at', Carbon::today()),
            'week' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'year' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ]),
            'total' => $query,
        };
    }

    public function resolvePeriod(?string $period): string
    {
        $period = $period ?: 'today';

        if (! in_array($period, self::PERIODS, true)) {
            throw new InvalidArgumentException('period must be one of: '.implode(', ', self::PERIODS));
        }

        return $period;
    }
}
