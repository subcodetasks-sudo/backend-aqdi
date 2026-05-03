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

    public function getTopCustomersByCompletedOrders(): array
    {
        return User::withCount(['contracts as completed_orders_count' => fn($q) => $q->where('is_completed', 1)->where('is_delete', 0)])
            ->orderBy('completed_orders_count', 'desc')
            ->limit(10)
            ->get(['id', 'fname', 'lname'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
            ->values()
            ->all();
    }

    public function getTopCustomersByIncompleteOrders(): array
    {
        return User::withCount(['contracts as incomplete_orders_count' => fn($q) => $q->where('is_completed', 0)->where('is_delete', 0)])
            ->orderBy('incomplete_orders_count', 'desc')
            ->limit(10)
            ->get(['id', 'fname', 'lname'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
            ->values()
            ->all();
    }

    public function getTopCustomersByTotalOrders(): array
    {
        return User::withCount(['contracts as total_orders_count' => fn($q) => $q->where('is_delete', 0)])
            ->orderBy('total_orders_count', 'desc')
            ->limit(10)
            ->get(['id', 'fname', 'lname'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
            ->values()
            ->all();
    }

    public function getTopCustomersByRefunds(): array
    {
        return User::select('users.id', 'users.fname', 'users.lname')
            ->selectRaw('SUM(payments.amount) as total_refunds')
            ->join('contracts', 'users.id', '=', 'contracts.user_id')
            ->join('payments', 'contracts.uuid', '=', 'payments.contract_uuid')
            ->where('payments.status', 'failed')
            ->groupBy('users.id', 'users.fname', 'users.lname')
            ->orderBy('total_refunds', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
            ->values()
            ->all();
    }

    public function getTopCustomersByRealEstates(): array
    {
        return User::withCount('realEstate')
            ->orderBy('real_estate_count', 'desc')
            ->limit(10)
            ->get(['id', 'fname', 'lname'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
            ->values()
            ->all();
    }

    public function getTopCustomersByUnits(): array
    {
        return User::withCount('unitReal')
            ->orderBy('unit_real_count', 'desc')
            ->limit(10)
            ->get(['id', 'fname', 'lname'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->fname . ' ' . $u->lname)])
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
