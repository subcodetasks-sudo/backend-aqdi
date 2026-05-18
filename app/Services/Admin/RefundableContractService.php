<?php

namespace App\Services\Admin;

use App\Models\ContractStatus;
use App\Models\RefundableContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

    /**
     * Period-scoped query for summaries (without list-only filters).
     */
    public function periodQuery(string $period): Builder
    {
        $query = $this->baseQuery();
        $this->applyPeriod($query, $period);

        return $query;
    }

    /**
     * Cards above the refunds table (موافقة الإدارة).
     *
     * @return array<string, mixed>
     */
    public function getManagementApprovalSummary(Builder $periodQuery): array
    {
        $approved = (clone $periodQuery)->where('refundable_contracts.admin_confirmed', true)->count();
        $notApproved = (clone $periodQuery)->where('refundable_contracts.admin_confirmed', false)->count();

        return [
            'approved' => [
                'key' => 'approved',
                'label_ar' => 'تمت الموافقة',
                'label_en' => 'Approved',
                'count' => $approved,
            ],
            'not_approved' => [
                'key' => 'not_approved',
                'label_ar' => 'لم تتم الموافقة',
                'label_en' => 'Not approved',
                'count' => $notApproved,
            ],
            'total' => $approved + $notApproved,
        ];
    }

    /**
     * Counts per contract_status from `contract_statuses` for refundable rows in period.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContractStatusSummary(Builder $periodQuery): array
    {
        $countsByStatus = (clone $periodQuery)
            ->join('contracts', 'refundable_contracts.contract_id', '=', 'contracts.id')
            ->whereNotNull('contracts.contract_status_id')
            ->select('contracts.contract_status_id', DB::raw('COUNT(*) as contracts_count'))
            ->groupBy('contracts.contract_status_id')
            ->pluck('contracts_count', 'contract_status_id');

        $statuses = ContractStatus::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'name', 'color', 'color_text', 'order']);

        if ($statuses->isEmpty()) {
            $statuses = ContractStatus::query()->orderBy('id')->get(['id', 'name', 'color', 'color_text', 'order']);
        }

        return $statuses->map(fn (ContractStatus $status) => [
            'contract_status_id' => $status->id,
            'name' => $status->name,
            'color' => $status->color,
            'color_text' => $status->color_text,
            'order' => $status->order,
            'count' => (int) ($countsByStatus[$status->id] ?? 0),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildIndexSummary(string $period): array
    {
        $periodQuery = $this->periodQuery($period);

        return [
            'management_approval' => $this->getManagementApprovalSummary($periodQuery),
            'contract_statuses' => $this->getContractStatusSummary($periodQuery),
        ];
    }
}
