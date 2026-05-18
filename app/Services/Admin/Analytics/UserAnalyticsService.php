<?php

namespace App\Services\Admin\Analytics;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserAnalyticsService
{
    use AnalyticsHelper;

    public function getNewUsersAnalytics(): array
    {
        $getByPeriod = fn($start, $end) => User::whereBetween('created_at', [$start, $end])->count();
        $today = $getByPeriod(Carbon::today(), Carbon::tomorrow());
        $week = $getByPeriod(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        $month = $getByPeriod(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $year = $getByPeriod(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
        $total = User::count();

        return [
            'today' => ['count' => $today, 'percentage_change' => $this->calculatePercentageChange($today, $getByPeriod(Carbon::yesterday(), Carbon::today()))],
            'week' => ['count' => $week, 'percentage_change' => $this->calculatePercentageChange($week, $getByPeriod(Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()))],
            'month' => ['count' => $month, 'percentage_change' => $this->calculatePercentageChange($month, $getByPeriod(Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()))],
            'year' => ['count' => $year, 'percentage_change' => $this->calculatePercentageChange($year, $getByPeriod(Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()))],
            'total' => $total,
        ];
    }

    public function getUserActivityRate(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', 1)->count();
        return [
            'rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
            'active_users_count' => $activeUsers,
            'total_users_count' => $totalUsers,
        ];
    }

    public function getInactiveUsers(): array
    {
        $totalUsers = User::count();
        $inactiveUsers = User::where('is_active', 0)->count();
        return [
            'count' => $inactiveUsers,
            'percentage' => $totalUsers > 0 ? round(($inactiveUsers / $totalUsers) * 100, 2) : 0,
        ];
    }

    public function getClientsWithRefundsCount(): int
    {
        $userIds = DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->where('payments.status', 'failed')
            ->distinct()
            ->pluck('contracts.user_id')
            ->filter();
        return User::whereIn('id', $userIds)->count();
    }

    public function countUsersWithCompletedOrders(): int
    {
        return User::withCount(['contracts as completed_count' => fn ($q) => $q->where('is_completed', 1)->where('is_delete', 0)])
            ->having('completed_count', '>', 0)
            ->count();
    }

    public function countUsersWithIncompleteOrders(): int
    {
        return User::withCount(['contracts as incomplete_count' => fn ($q) => $q->where('is_completed', 0)->where('is_delete', 0)])
            ->having('incomplete_count', '>', 0)
            ->count();
    }

    public function countUsersWithOrders(): int
    {
        return User::has('contracts')->count();
    }

    public function countUsersWithRealEstates(): int
    {
        return User::has('realEstate')->count();
    }

    public function countUsersWithUnits(): int
    {
        return User::has('unitReal')->count();
    }

    public function getTopCustomersByCompletedOrders(int $limit = 10): array
    {
        return User::withCount(['contracts as completed_orders_count' => fn ($q) => $q->where('is_completed', 1)->where('is_delete', 0)])
            ->having('completed_orders_count', '>', 0)
            ->orderByDesc('completed_orders_count')
            ->limit($limit)
            ->get(['id', 'fname', 'lname'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => $u->completed_orders_count,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomersByIncompleteOrders(int $limit = 10): array
    {
        return User::withCount(['contracts as incomplete_orders_count' => fn ($q) => $q->where('is_completed', 0)->where('is_delete', 0)])
            ->having('incomplete_orders_count', '>', 0)
            ->orderByDesc('incomplete_orders_count')
            ->limit($limit)
            ->get(['id', 'fname', 'lname'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => $u->incomplete_orders_count,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomersByTotalOrders(int $limit = 10): array
    {
        return User::withCount(['contracts as total_orders_count' => fn ($q) => $q->where('is_delete', 0)])
            ->having('total_orders_count', '>', 0)
            ->orderByDesc('total_orders_count')
            ->limit($limit)
            ->get(['id', 'fname', 'lname'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => $u->total_orders_count,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomersByRefunds(int $limit = 10): array
    {
        return User::query()
            ->select('users.id', 'users.fname', 'users.lname')
            ->selectRaw('COUNT(DISTINCT contracts.id) as refunds_count')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as total_refunds')
            ->join('contracts', 'users.id', '=', 'contracts.user_id')
            ->join('payments', 'contracts.uuid', '=', 'payments.contract_uuid')
            ->where('payments.status', 'failed')
            ->groupBy('users.id', 'users.fname', 'users.lname')
            ->orderByDesc('total_refunds')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => (int) $u->refunds_count,
                'total_refunds' => (float) $u->total_refunds,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomersByRealEstates(int $limit = 10): array
    {
        return User::withCount('realEstate')
            ->having('real_estate_count', '>', 0)
            ->orderByDesc('real_estate_count')
            ->limit($limit)
            ->get(['id', 'fname', 'lname'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => $u->real_estate_count,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomersByUnits(int $limit = 10): array
    {
        return User::withCount('unitReal')
            ->having('unit_real_count', '>', 0)
            ->orderByDesc('unit_real_count')
            ->limit($limit)
            ->get(['id', 'fname', 'lname'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->fname.' '.$u->lname),
                'count' => $u->unit_real_count,
            ])
            ->values()
            ->all();
    }

    public function getTopCustomers(): array
    {
        return [
            'completed_orders' => $this->getTopCustomersByCompletedOrders(),
            'incomplete_orders' => $this->getTopCustomersByIncompleteOrders(),
            'total_orders' => $this->getTopCustomersByTotalOrders(),
            'refunds' => $this->getTopCustomersByRefunds(),
            'real_estates' => $this->getTopCustomersByRealEstates(),
            'units' => $this->getTopCustomersByUnits(),
        ];
    }
}
