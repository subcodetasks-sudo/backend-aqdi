<?php

namespace App\Services\Admin\Analytics;

use App\Models\Contract;
use App\Models\ContractStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ContractStatusAnalyticsService
{
    use AnalyticsHelper;

    public const DEFAULT_STATUS_ID = 2;

    public function resolveStatus(int $contractStatusId): ContractStatus
    {
        $status = ContractStatus::query()->find($contractStatusId);
        if (! $status) {
            throw new ModelNotFoundException('Contract status not found.');
        }

        return $status;
    }

    /**
     * @return array<string, array{value: int|float, percentage_change: float|null}>
     */
    public function getPeriodMetrics(int $contractStatusId): array
    {
        $countBetween = function ($start, $end) use ($contractStatusId) {
            return $this->baseQuery($contractStatusId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        };

        $today = $countBetween(Carbon::today(), Carbon::tomorrow());
        $yesterday = $countBetween(Carbon::yesterday(), Carbon::today());
        $week = $countBetween(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        $lastWeek = $countBetween(
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        );
        $month = $countBetween(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $lastMonth = $countBetween(
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        );
        $year = $countBetween(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
        $lastYear = $countBetween(
            Carbon::now()->subYear()->startOfYear(),
            Carbon::now()->subYear()->endOfYear()
        );
        $total = $this->baseQuery($contractStatusId)->count();

        return [
            'today' => [
                'value' => $today,
                'percentage_change' => $this->calculatePercentageChange($today, $yesterday),
            ],
            'week' => [
                'value' => $week,
                'percentage_change' => $this->calculatePercentageChange($week, $lastWeek),
            ],
            'month' => [
                'value' => $month,
                'percentage_change' => $this->calculatePercentageChange($month, $lastMonth),
            ],
            'year' => [
                'value' => $year,
                'percentage_change' => $this->calculatePercentageChange($year, $lastYear),
            ],
            'total' => [
                'value' => $total,
                'percentage_change' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContractsForPeriod(int $contractStatusId, string $period, int $limit = 10): array
    {
        $query = $this->baseQuery($contractStatusId)->with('user:id,fname,lname');

        match ($period) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week' => $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'year' => $query->whereBetween('created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ]),
            'total' => null,
            default => throw new \InvalidArgumentException("Unknown period: {$period}"),
        };

        return $query
            ->latest()
            ->limit($limit)
            ->get(['id', 'uuid', 'user_id', 'contract_status_id', 'created_at'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'user_id' => $c->user_id,
                'user_name' => $c->user
                    ? trim($c->user->fname.' '.$c->user->lname)
                    : null,
                'created_at' => $c->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    protected function baseQuery(int $contractStatusId)
    {
        return Contract::query()
            ->where('contract_status_id', $contractStatusId)
            ->where('is_delete', 0);
    }

    public function paginateContracts(
        int $contractStatusId,
        ?string $period = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $this->resolveStatus($contractStatusId);

        $query = $this->baseQuery($contractStatusId)
            ->with([
                'user',
                'receivedContract.employee',
                'contractStatus',
            ]);

        if ($period) {
            match ($period) {
                'today' => $query->whereDate('created_at', Carbon::today()),
                'week' => $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]),
                'month' => $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]),
                'year' => $query->whereBetween('created_at', [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ]),
                'total' => null,
                default => throw new \InvalidArgumentException("Unknown period: {$period}"),
            };
        }

        return $query->latest()->paginate($perPage);
    }
}
