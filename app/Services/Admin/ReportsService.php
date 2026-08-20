<?php

namespace App\Services\Admin;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\CouponUsage;
use App\Models\OperatingExpense;
use App\Models\Payment;
use App\Models\RefundableContract;
use App\Models\Setting;
use App\Models\User;
use App\Support\Concerns\ResolvesReportPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backing service for the admin Reports page tabs (/admin/reports/*).
 *
 * "Marketing" (source/UTM/ad-spend attribution) and the conversion funnel's
 * site-visit step are intentionally not implemented here — this app does not
 * track order source or web analytics today, so those numbers can't be real.
 */
class ReportsService
{
    use ResolvesReportPeriod;

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function orders(array $filter, ?string $contractType, ?int $employeeId): array
    {
        $range = $filter['range'];
        $base = $this->ordersBaseQuery($range, $contractType, $employeeId);

        $total = (clone $base)->count();
        $new = (clone $base)->where('contract_status_id', ContractStatus::NEW_ID)->count();
        $paid = (clone $base)->where('is_completed', 1)->count();
        $draft = (clone $base)->where('is_draft', true)->count();
        $incomplete = (clone $base)->where('is_completed', 0)->count();
        $returned = (clone $base)->where('contract_status_id', ContractStatus::RETURN_ID)->count();
        $canceled = $this->canceledContractsQuery($range, $contractType, $employeeId)->count();

        return [
            'kpis' => [
                'total' => $total,
                'new' => $new,
                'paid' => $paid,
                'draft' => $draft,
                'incomplete' => $incomplete,
                'canceled' => $canceled,
                'returned' => $returned,
                'avg_completion_minutes' => $this->avgCompletionMinutes($range, $contractType, $employeeId),
            ],
            'by_employee' => $this->ordersByEmployee($range, $contractType),
            'by_contract_type' => $this->ordersByContractType($range, $employeeId),
            'by_stage' => $this->statusBreakdown($range, $contractType, $employeeId),
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function sales(array $filter, ?string $contractType, ?int $employeeId): array
    {
        $range = $filter['range'];
        $totals = $this->salesTotals($range, $contractType, $employeeId);
        $revenueBreakdown = $this->revenueByTypeAndDuration($range, $employeeId, $contractType);

        return [
            'kpis' => [
                'total_sales' => $totals['total_sales'],
                'payments_count' => $totals['payments_count'],
                'avg_order_value' => $totals['payments_count'] > 0
                    ? $this->moneyValue($totals['total_sales'] / $totals['payments_count'])
                    : 0,
                'discounts_used' => $totals['discounts_total'],
                'refunds' => $totals['refunds_total'],
                'net_revenue' => $totals['net_revenue'],
            ],
            'by_period' => $this->salesByQuickPeriod(),
            'daily' => $this->salesDaily($range, $contractType, $employeeId),
            'revenue_by_contract_type' => $revenueBreakdown['by_type'],
            'revenue_by_duration' => $revenueBreakdown['by_duration'],
            'summary' => [
                'discounts_granted' => $totals['discounts_total'],
                'discounted_orders_count' => $totals['discounted_orders_count'],
                'refunds_total' => $totals['refunds_total'],
                'refund_rate_percent' => $totals['total_sales'] > 0
                    ? (int) round(($totals['refunds_total'] / $totals['total_sales']) * 100)
                    : 0,
                'net_revenue_after_refunds' => $totals['net_revenue'],
            ],
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function profits(array $filter, bool $includeSalaries): array
    {
        $range = $filter['range'];
        $totals = $this->salesTotals($range, null, null);
        $settings = Setting::query()->first();

        $gatewayFeePercent = (float) ($settings->moyasar_fee_percent ?? 0);
        $gatewayFee = round($totals['net_revenue'] * $gatewayFeePercent / 100, 2);
        $operatingExpenses = $this->operatingExpensesTotal($range);
        $adSpend = (float) ($settings->marketing_budget ?? 0);
        $salaries = (float) ($settings->monthly_salaries ?? 0);

        $grossProfit = round($totals['net_revenue'] - $gatewayFee, 2);
        $netProfitBeforeSalaries = round($grossProfit - $adSpend - $operatingExpenses, 2);
        $netProfit = $includeSalaries ? round($netProfitBeforeSalaries - $salaries, 2) : $netProfitBeforeSalaries;

        $paidOrders = $this->ordersBaseQuery($range, null, null)->where('is_completed', 1)->count();

        $pnl = [
            ['label' => 'دخل العملاء (المحصّل)', 'value' => $this->moneyValue($totals['total_sales'])],
            ['label' => 'الاسترجاعات', 'value' => -$this->moneyValue($totals['refunds_total'])],
            ['label' => 'صافي الإيرادات', 'value' => $this->moneyValue($totals['net_revenue']), 'is_subtotal' => true],
            ['label' => 'رسوم بوابة الدفع', 'value' => -$this->moneyValue($gatewayFee)],
            ['label' => 'إجمالي الربح', 'value' => $this->moneyValue($grossProfit), 'is_subtotal' => true],
            ['label' => 'مصاريف الإعلانات', 'value' => -$this->moneyValue($adSpend)],
            ['label' => 'مصاريف تشغيلية', 'value' => -$this->moneyValue($operatingExpenses)],
        ];

        if ($includeSalaries) {
            $pnl[] = ['label' => 'الرواتب', 'value' => -$this->moneyValue($salaries)];
        }

        $pnl[] = ['label' => 'صافي الربح', 'value' => $this->moneyValue($netProfit), 'is_total' => true];

        return [
            'kpis' => [
                'customer_income' => $this->moneyValue($totals['total_sales']),
                'gross_profit' => $this->moneyValue($grossProfit),
                'net_profit' => $this->moneyValue($netProfit),
                'margin_percent' => $totals['total_sales'] > 0
                    ? (int) round(($netProfit / $totals['total_sales']) * 100)
                    : 0,
                'profit_per_order' => $paidOrders > 0 ? $this->moneyValue($netProfit / $paidOrders) : 0,
                'ad_spend' => $this->moneyValue($adSpend),
                'salaries_included' => $includeSalaries,
            ],
            'service_revenue' => $this->revenueByTypeAndDuration($range, null)['services'],
            'pnl' => $pnl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profitSettings(bool $includeSalaries): array
    {
        $settings = Setting::query()->first() ?? Setting::query()->create([]);

        $payload = [
            'moyasar_fee_percent' => $settings->moyasar_fee_percent !== null ? (float) $settings->moyasar_fee_percent : null,
            'operating_budget' => $settings->operating_budget !== null ? (float) $settings->operating_budget : null,
            'marketing_budget' => $settings->marketing_budget !== null ? (float) $settings->marketing_budget : null,
        ];

        if ($includeSalaries) {
            $payload['monthly_salaries'] = $settings->monthly_salaries !== null ? (float) $settings->monthly_salaries : null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateProfitSettings(array $data, bool $canEditSalaries): array
    {
        $settings = Setting::query()->first() ?? Setting::query()->create([]);

        $update = array_intersect_key($data, array_flip(['moyasar_fee_percent', 'operating_budget', 'marketing_budget']));

        if ($canEditSalaries && array_key_exists('monthly_salaries', $data)) {
            $update['monthly_salaries'] = $data['monthly_salaries'];
        }

        $settings->update($update);

        return $this->profitSettings($canEditSalaries);
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function customers(array $filter): array
    {
        $range = $filter['range'];

        $periodContracts = Contract::query()->notDeleted()->reachedAdminOrderStep();
        $this->applyDateRange($periodContracts, 'created_at', $range);

        $customerIds = (clone $periodContracts)->pluck('user_id')->filter()->unique();
        $total = $customerIds->count();

        $newCustomers = 0;
        if ($customerIds->isNotEmpty()) {
            $firstContractAtByUser = Contract::query()
                ->whereIn('user_id', $customerIds)
                ->notDeleted()
                ->groupBy('user_id')
                ->selectRaw('user_id, MIN(created_at) as first_created_at')
                ->pluck('first_created_at', 'user_id');

            $newCustomers = $range === null
                ? $firstContractAtByUser->count()
                : $firstContractAtByUser->filter(
                    fn ($firstCreatedAt) => Carbon::parse($firstCreatedAt)->between($range[0], $range[1])
                )->count();
        }

        $incompleteCustomers = (clone $periodContracts)->where('is_completed', 0)->pluck('user_id')->filter()->unique()->count();
        $totalContracts = (clone $periodContracts)->count();

        $topCustomers = $this->topCustomers($range);

        return [
            'kpis' => [
                'total' => $total,
                'new' => $newCustomers,
                'returning' => max(0, $total - $newCustomers),
                'avg_contracts_per_customer' => $total > 0 ? round($totalContracts / $total, 1) : 0,
                'incomplete' => $incompleteCustomers,
            ],
            'segments' => [
                ['label' => 'عملاء جدد', 'value' => $newCustomers],
                ['label' => 'عملاء عائدون', 'value' => max(0, $total - $newCustomers)],
            ],
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function performance(array $filter): array
    {
        $range = $filter['range'];
        $base = $this->ordersBaseQuery($range, null, null);
        $doneIds = $this->doneStatusIds();
        $notOpenIds = $this->notOpenStatusIds();

        $totalCount = (clone $base)->count();
        $paidCount = (clone $base)->where('is_completed', 1)->count();
        $completedCount = $doneIds === [] ? 0 : (clone $base)->whereIn('contract_status_id', $doneIds)->count();
        $refundedCount = $this->refundedCount($range);
        $canceledCount = $this->canceledContractsQuery($range, null, null)->count();
        $activeCount = (clone $base)
            ->when($notOpenIds !== [], fn ($q) => $q->where(function ($sq) use ($notOpenIds) {
                $sq->whereNull('contract_status_id')->orWhereNotIn('contract_status_id', $notOpenIds);
            }))
            ->count();

        $revenue = $this->salesTotals($range, null, null)['total_sales'];
        $delayed = $this->delayedOpenContractsCount($range);

        return [
            'kpis' => [
                'revenue' => $this->moneyValue($revenue),
                'refunded_count' => $refundedCount,
                'delayed_count' => $delayed,
                'canceled_count' => $canceledCount,
                'active_count' => $activeCount,
                'total_count' => $totalCount,
            ],
            'conversion_funnel' => [
                ['step' => 'order_started', 'label' => 'بدء الطلب', 'value' => $totalCount],
                ['step' => 'paid', 'label' => 'دفع', 'value' => $paidCount],
                ['step' => 'completed', 'label' => 'إتمام', 'value' => $completedCount],
            ],
            'daily_orders' => $this->dailyOrders($range),
            'orders_by_status' => $this->statusBreakdown($range, null, null),
            'revenue_by_payment_method' => $this->revenueByPaymentMethod($range),
            'operational_metrics' => $this->operationalMetrics($range, $totalCount, $delayed),
            'unit_economics' => $this->revenueByTypeAndDuration($range, null)['unit_economics'],
        ];
    }

    // ------------------------------------------------------------------
    // Shared query builders
    // ------------------------------------------------------------------

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function ordersBaseQuery(?array $range, ?string $contractType, ?int $employeeId)
    {
        $query = Contract::query()->notDeleted()->reachedAdminOrderStep();
        $this->applyDateRange($query, 'created_at', $range);

        if ($contractType !== null) {
            $query->where('contract_type', $contractType);
        }

        if ($employeeId !== null) {
            $query->whereHas('receivedContract', fn ($q) => $q->where('employee_id', $employeeId));
        }

        return $query;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function canceledContractsQuery(?array $range, ?string $contractType, ?int $employeeId)
    {
        $query = Contract::query()->where('is_delete', 1);
        $this->applyDateRange($query, 'created_at', $range);

        if ($contractType !== null) {
            $query->where('contract_type', $contractType);
        }

        if ($employeeId !== null) {
            $query->whereHas('receivedContract', fn ($q) => $q->where('employee_id', $employeeId));
        }

        return $query;
    }

    private function applyDateRange($query, string $column, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween($column, [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()]);
    }

    /**
     * @return list<int>
     */
    private function doneStatusIds(): array
    {
        return ContractStatus::query()
            ->where(function ($q) {
                $q->whereKey(ContractStatus::WAITING_SUPERVISOR_ID)
                    ->orWhere('name', 'مكتمل')
                    ->orWhere('name', 'like', '%بانتظار المشرف%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function notOpenStatusIds(): array
    {
        $ids = $this->doneStatusIds();

        $closed = ContractStatus::query()
            ->where(function ($q) {
                $q->whereKey(ContractStatus::RETURN_ID)
                    ->orWhereIn('name', ['ملغى', 'مكتمل', 'مسترجع', 'استرجاع']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$ids, ...$closed]));
    }

    // ------------------------------------------------------------------
    // Orders tab helpers
    // ------------------------------------------------------------------

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function avgCompletionMinutes(?array $range, ?string $contractType, ?int $employeeId): ?int
    {
        $query = $this->ordersBaseQuery($range, $contractType, $employeeId)->where('is_completed', 1);

        $rows = $query
            ->select(['contracts.id', 'contracts.uuid', 'contracts.created_at'])
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $uuids = $rows->pluck('uuid')->all();
        $firstPaymentAt = Payment::query()
            ->successful()
            ->where(function ($q) use ($uuids) {
                foreach ($uuids as $uuid) {
                    $q->orWhere('contract_uuid', $uuid)->orWhere('contract_uuid', 'like', $uuid.'-%');
                }
            })
            ->orderBy('created_at')
            ->get(['contract_uuid', 'created_at'])
            ->groupBy(fn ($p) => (string) preg_replace('/-.*$/', '', (string) $p->contract_uuid))
            ->map(fn ($group) => $group->first()->created_at);

        $minutes = [];
        foreach ($rows as $contract) {
            $paidAt = $firstPaymentAt->get((string) $contract->uuid);
            if ($paidAt === null || $contract->created_at === null) {
                continue;
            }
            $minutes[] = Carbon::parse($contract->created_at)->diffInMinutes(Carbon::parse($paidAt));
        }

        if ($minutes === []) {
            return null;
        }

        return (int) round(array_sum($minutes) / count($minutes));
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{employee_id: int, label: string, value: int}>
     */
    private function ordersByEmployee(?array $range, ?string $contractType): array
    {
        $query = DB::table('received_contracts')
            ->join('contracts', 'contracts.id', '=', 'received_contracts.contract_id')
            ->join('employees', 'employees.id', '=', 'received_contracts.employee_id')
            ->where('contracts.is_delete', 0);

        $this->applyDateRange($query, 'received_contracts.created_at', $range);

        if ($contractType !== null) {
            $query->where('contracts.contract_type', $contractType);
        }

        return $query
            ->groupBy('received_contracts.employee_id', 'employees.name')
            ->selectRaw('received_contracts.employee_id, employees.name as label, COUNT(*) as aggregate')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (int) $row->employee_id,
                'label' => $row->label,
                'value' => (int) $row->aggregate,
            ])
            ->all();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{label: string, value: int}>
     */
    private function ordersByContractType(?array $range, ?int $employeeId): array
    {
        $rows = $this->ordersBaseQuery($range, null, $employeeId)
            ->groupBy('contract_type')
            ->selectRaw('contract_type, COUNT(*) as aggregate')
            ->pluck('aggregate', 'contract_type');

        return collect(Contract::CONTRACT_TYPES)
            ->map(fn (string $type) => [
                'label' => Contract::contractTypeLabel($type),
                'value' => (int) ($rows[$type] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{stage: string, label: string, value: int}>
     */
    private function statusBreakdown(?array $range, ?string $contractType, ?int $employeeId): array
    {
        $rows = $this->ordersBaseQuery($range, $contractType, $employeeId)
            ->whereNotNull('contract_status_id')
            ->groupBy('contract_status_id')
            ->selectRaw('contract_status_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'contract_status_id');

        return ContractStatus::query()
            ->orderBy('order')
            ->get(['id', 'name'])
            ->map(fn (ContractStatus $status) => [
                'stage' => 'status_'.$status->id,
                'label' => $status->name,
                'value' => (int) ($rows[$status->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // Sales / revenue helpers
    // ------------------------------------------------------------------

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{total_sales: float, payments_count: int, discounts_total: float, discounted_orders_count: int, refunds_total: float, net_revenue: float}
     */
    private function salesTotals(?array $range, ?string $contractType, ?int $employeeId): array
    {
        $paymentsQuery = Payment::query()->successful();
        $this->applyPaymentDateRange($paymentsQuery, $range);

        if ($contractType !== null || $employeeId !== null) {
            $paymentsQuery->whereIn('contract_uuid', $this->filteredContractUuids($range, $contractType, $employeeId));
        }

        $totalSales = (float) (clone $paymentsQuery)->sum('amount');
        $paymentsCount = (clone $paymentsQuery)->count();

        $discounts = $this->couponDiscounts($range);
        $refunds = $this->refundsAmount($range);

        return [
            'total_sales' => $this->moneyValue($totalSales),
            'payments_count' => $paymentsCount,
            'discounts_total' => $discounts['total'],
            'discounted_orders_count' => $discounts['orders_count'],
            'refunds_total' => $refunds,
            'net_revenue' => $this->moneyValue($totalSales - $refunds),
        ];
    }

    /**
     * Contract uuids (with any "-suffix" payment variants) matching contract_type/employee filters, for a period.
     *
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<string>
     */
    private function filteredContractUuids(?array $range, ?string $contractType, ?int $employeeId): array
    {
        return $this->ordersBaseQuery($range, $contractType, $employeeId)
            ->pluck('uuid')
            ->map(fn ($uuid) => (string) $uuid)
            ->all();
    }

    private function applyPaymentDateRange($query, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween('payment_date', [$range[0]->toDateString(), $range[1]->toDateString()]);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{total: float, orders_count: int}
     */
    private function couponDiscounts(?array $range): array
    {
        $query = CouponUsage::query()->with('coupon');
        $this->applyDateRange($query, 'used_at', $range);
        $usages = $query->get();

        if ($usages->isEmpty()) {
            return ['total' => 0.0, 'orders_count' => 0];
        }

        $uuids = $usages->pluck('contract_uuid')->filter()->unique()->values()->all();
        $amountsByUuid = Payment::query()
            ->successful()
            ->where(function ($q) use ($uuids) {
                foreach ($uuids as $uuid) {
                    $q->orWhere('contract_uuid', $uuid)->orWhere('contract_uuid', 'like', $uuid.'-%');
                }
            })
            ->get(['contract_uuid', 'amount'])
            ->groupBy(fn ($p) => (string) preg_replace('/-.*$/', '', (string) $p->contract_uuid))
            ->map(fn ($group) => (float) $group->sum('amount'));

        $total = 0.0;
        $contractsWithDiscount = collect();

        foreach ($usages as $usage) {
            $coupon = $usage->coupon;
            if ($coupon === null || $usage->contract_uuid === null) {
                continue;
            }

            $paidAmount = (float) ($amountsByUuid[(string) $usage->contract_uuid] ?? 0);

            if ($coupon->type_coupon === 'ratio') {
                // paidAmount is the post-discount price; back out the discount from the ratio.
                $ratio = (float) $coupon->value_coupon;
                $discount = $ratio < 100 ? round(($paidAmount / (1 - $ratio / 100)) - $paidAmount, 2) : $paidAmount;
            } else {
                $discount = (float) $coupon->value_coupon;
            }

            $total += $discount;
            $contractsWithDiscount->push((string) $usage->contract_uuid);
        }

        return [
            'total' => $this->moneyValue($total),
            'orders_count' => $contractsWithDiscount->unique()->count(),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function refundsAmount(?array $range): float
    {
        $query = RefundableContract::query()->where('admin_confirmed', true);
        $this->applyDateRange($query, 'created_at', $range);

        return $this->moneyValue((float) $query->sum('refund_amount'));
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function refundedCount(?array $range): int
    {
        $query = RefundableContract::query()->where('admin_confirmed', true);
        $this->applyDateRange($query, 'created_at', $range);

        return $query->count();
    }

    /**
     * @return list<array{label: string, value: float}>
     */
    private function salesByQuickPeriod(): array
    {
        $now = now();
        $ranges = [
            'اليوم' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'هذا الشهر' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'هذه السنة' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
        ];

        $result = [];
        foreach ($ranges as $label => $range) {
            $query = Payment::query()->successful();
            $this->applyPaymentDateRange($query, $range);
            $result[] = ['label' => $label, 'value' => $this->moneyValue((float) $query->sum('amount'))];
        }

        return $result;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{date: string, value: float}>
     */
    private function salesDaily(?array $range, ?string $contractType, ?int $employeeId): array
    {
        if ($range === null) {
            return [];
        }

        $paymentsQuery = Payment::query()->successful();
        $this->applyPaymentDateRange($paymentsQuery, $range);

        if ($contractType !== null || $employeeId !== null) {
            $paymentsQuery->whereIn('contract_uuid', $this->filteredContractUuids($range, $contractType, $employeeId));
        }

        $byDate = $paymentsQuery
            ->groupBy('payment_date')
            ->selectRaw('payment_date, SUM(amount) as aggregate')
            ->pluck('aggregate', 'payment_date');

        $days = [];
        $cursor = $range[0]->copy()->startOfDay();
        $end = $range[1]->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = ['date' => $key, 'value' => $this->moneyValue((float) ($byDate[$key] ?? 0))];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Revenue grouped by contract_type and by contract period (duration), shaped for
     * sales.revenue_by_contract_type / sales.revenue_by_duration / profits.service_revenue /
     * performance.unit_economics from the same underlying join.
     *
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{by_type: list<array<string, mixed>>, by_duration: list<array<string, mixed>>, services: list<array<string, mixed>>, unit_economics: list<array<string, mixed>>}
     */
    private function revenueByTypeAndDuration(?array $range, ?int $employeeId, ?string $contractType = null): array
    {
        $query = Payment::query()
            ->join('contracts', function ($join) {
                $join->on(DB::raw('contracts.uuid'), '=', DB::raw('SUBSTRING_INDEX(payments.contract_uuid, \'-\', 1)'));
            })
            ->where('payments.status', 'success')
            ->where('contracts.is_delete', 0);

        $this->applyPaymentDateRange($query, $range);

        if ($contractType !== null) {
            $query->where('contracts.contract_type', $contractType);
        }

        if ($employeeId !== null) {
            $query->whereExists(function ($sub) use ($employeeId) {
                $sub->selectRaw('1')
                    ->from('received_contracts')
                    ->whereColumn('received_contracts.contract_id', 'contracts.id')
                    ->where('received_contracts.employee_id', $employeeId);
            });
        }

        $byType = (clone $query)
            ->groupBy('contracts.contract_type')
            ->selectRaw('contracts.contract_type, SUM(payments.amount) as revenue, COUNT(*) as orders_count')
            ->get()
            ->keyBy('contract_type');

        $typeTotal = (float) $byType->sum('revenue');

        $byTypeRows = collect(Contract::CONTRACT_TYPES)
            ->map(function (string $type) use ($byType, $typeTotal) {
                $row = $byType->get($type);
                $revenue = (float) ($row->revenue ?? 0);

                return [
                    'label' => Contract::contractTypeLabel($type),
                    'value' => $this->moneyValue($revenue),
                    'orders_count' => (int) ($row->orders_count ?? 0),
                    'percent' => $typeTotal > 0 ? (int) round($revenue / $typeTotal * 100) : 0,
                ];
            })
            ->values();

        $byDurationRaw = (clone $query)
            ->join('contract_periods', 'contract_periods.id', '=', 'contracts.contract_term_in_years')
            ->groupBy('contract_periods.id', 'contract_periods.note_ar')
            ->selectRaw('contract_periods.id, contract_periods.note_ar as label, SUM(payments.amount) as revenue, COUNT(*) as orders_count')
            ->orderByDesc('revenue')
            ->get();

        $durationTotal = (float) $byDurationRaw->sum('revenue');

        $byDuration = $byDurationRaw->map(fn ($row) => [
            'label' => $row->label,
            'value' => $this->moneyValue((float) $row->revenue),
            'orders_count' => (int) $row->orders_count,
            'percent' => $durationTotal > 0 ? (int) round(((float) $row->revenue) / $durationTotal * 100) : 0,
        ])->values()->all();

        $services = $byTypeRows->map(fn (array $row) => [
            'service' => $row['label'],
            'label' => $row['label'],
            'revenue' => $row['value'],
            'orders_count' => $row['orders_count'],
            'revenue_share_percent' => $row['percent'],
        ])->values()->all();

        $unitEconomics = collect($byDuration)->map(fn (array $row) => [
            'service' => $row['label'],
            'label' => $row['label'],
            'qty' => $row['orders_count'],
            'value' => $row['value'],
            'percent' => $row['percent'],
        ])->values()->all();

        return [
            'by_type' => $byTypeRows->map(fn (array $row) => [
                'label' => $row['label'],
                'value' => $row['value'],
            ])->values()->all(),
            'by_duration' => collect($byDuration)->map(fn (array $row) => [
                'label' => $row['label'],
                'value' => $row['value'],
            ])->values()->all(),
            'services' => $services,
            'unit_economics' => $unitEconomics,
        ];
    }

    // ------------------------------------------------------------------
    // Profits helpers
    // ------------------------------------------------------------------

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function operatingExpensesTotal(?array $range): float
    {
        $query = OperatingExpense::query();
        $this->applyDateRange($query, 'created_at', $range);

        return $this->moneyValue((float) $query->sum('amount'));
    }

    // ------------------------------------------------------------------
    // Customers helpers
    // ------------------------------------------------------------------

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    private function topCustomers(?array $range, int $limit = 20): array
    {
        $contractsQuery = Contract::query()->notDeleted()->reachedAdminOrderStep();
        $this->applyDateRange($contractsQuery, 'created_at', $range);

        $userIds = (clone $contractsQuery)->pluck('user_id')->filter()->unique();
        if ($userIds->isEmpty()) {
            return [];
        }

        $contractStats = (clone $contractsQuery)
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as contracts_count, SUM(is_completed) as paid_count')
            ->get()
            ->keyBy('user_id');

        $uuidsByUser = (clone $contractsQuery)
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'uuid'])
            ->groupBy('user_id');

        $spendingByUser = [];
        foreach ($uuidsByUser as $userId => $contracts) {
            $uuids = $contracts->pluck('uuid')->map(fn ($u) => (string) $u)->all();
            $spendingByUser[$userId] = (float) Payment::query()
                ->successful()
                ->where(function ($q) use ($uuids) {
                    foreach ($uuids as $uuid) {
                        $q->orWhere('contract_uuid', $uuid)->orWhere('contract_uuid', 'like', $uuid.'-%');
                    }
                })
                ->sum('amount');
        }

        arsort($spendingByUser);
        $topUserIds = array_slice(array_keys($spendingByUser), 0, $limit);

        if ($topUserIds === []) {
            return [];
        }

        $users = User::query()->whereIn('id', $topUserIds)->get()->keyBy('id');

        return collect($topUserIds)
            ->map(function ($userId) use ($users, $contractStats, $spendingByUser) {
                $user = $users->get($userId);
                $stats = $contractStats->get($userId);

                return [
                    'customer_id' => (int) $userId,
                    'name' => $user?->name,
                    'mobile' => $user?->mobile,
                    'contracts_count' => (int) ($stats->contracts_count ?? 0),
                    'paid_count' => (int) ($stats->paid_count ?? 0),
                    'total_spending' => $this->moneyValue($spendingByUser[$userId] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // Performance helpers
    // ------------------------------------------------------------------

    /**
     * Open contracts (not received) waiting more than 24h since creation, within period.
     *
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function delayedOpenContractsCount(?array $range): int
    {
        $threshold = now()->subHours(24);

        $query = Contract::query()
            ->notDeleted()
            ->reachedAdminOrderStep()
            ->whereDoesntHave('receivedContract')
            ->where('created_at', '<=', $threshold);

        $this->applyDateRange($query, 'created_at', $range);

        return $query->count();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{date: string, value: int}>
     */
    private function dailyOrders(?array $range): array
    {
        if ($range === null) {
            return [];
        }

        $byDate = $this->ordersBaseQuery($range, null, null)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as aggregate')
            ->groupBy('d')
            ->pluck('aggregate', 'd');

        $days = [];
        $cursor = $range[0]->copy()->startOfDay();
        $end = $range[1]->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = ['date' => $key, 'value' => (int) ($byDate[$key] ?? 0)];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{method: string, label: string, value: float}>
     */
    private function revenueByPaymentMethod(?array $range): array
    {
        $query = Payment::query()->successful();
        $this->applyPaymentDateRange($query, $range);

        return $query
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as aggregate')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->payment_method,
                'label' => (string) $row->payment_method,
                'value' => $this->moneyValue((float) $row->aggregate),
            ])
            ->all();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array<string, mixed>
     */
    private function operationalMetrics(?array $range, int $totalOrders, int $delayedCount): array
    {
        $query = DB::table('received_contracts')
            ->join('contracts', 'contracts.id', '=', 'received_contracts.contract_id')
            ->where('contracts.is_delete', 0)
            ->select([
                'received_contracts.created_at as received_at',
                'received_contracts.date_of_received',
                'contracts.created_at as contract_created_at',
            ]);

        $this->applyDateRange($query, 'received_contracts.created_at', $range);

        $rows = $query->get();
        $slaSeconds = (int) config('employee_kpis.receive_sla_minutes', 5) * 60;

        $seconds = [];
        $slaMet = 0;
        foreach ($rows as $row) {
            $receivedAt = $row->received_at
                ? Carbon::parse($row->received_at)
                : ($row->date_of_received ? Carbon::parse($row->date_of_received)->startOfDay() : null);
            if ($receivedAt === null || $row->contract_created_at === null) {
                continue;
            }
            $diff = Carbon::parse($row->contract_created_at)->diffInSeconds($receivedAt);
            $seconds[] = $diff;
            if ($diff <= $slaSeconds) {
                $slaMet++;
            }
        }

        $total = count($seconds);

        return [
            'total_orders' => $totalOrders,
            'avg_receive_seconds' => $total > 0 ? (int) round(array_sum($seconds) / $total) : null,
            'longest_wait_seconds' => $total > 0 ? (int) max($seconds) : null,
            'sla_percent' => $total > 0 ? (int) round(($slaMet / $total) * 100) : 100,
            'delayed_over_24h_count' => $delayedCount,
        ];
    }

    // ------------------------------------------------------------------
    // Formatting
    // ------------------------------------------------------------------

    private function moneyValue(float $amount): int|float
    {
        $rounded = round($amount, 2);

        return abs($rounded - (int) round($rounded)) < 0.005
            ? (int) round($rounded)
            : $rounded;
    }
}
