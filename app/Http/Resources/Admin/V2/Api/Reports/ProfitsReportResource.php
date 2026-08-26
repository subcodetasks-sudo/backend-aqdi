<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class ProfitsReportResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kpis = is_array($this->resource['kpis'] ?? null) ? $this->resource['kpis'] : [];

        return array_merge($this->periodFields(), [
            'kpis' => [
                'customer_income' => $kpis['customer_income'] ?? 0,
                'gross_profit' => $kpis['gross_profit'] ?? 0,
                'net_profit' => $kpis['net_profit'] ?? 0,
                'margin_percent' => (int) ($kpis['margin_percent'] ?? 0),
                'profit_per_order' => $kpis['profit_per_order'] ?? 0,
                'ad_spend' => $kpis['ad_spend'] ?? 0,
                'ejar_platform_fees' => $kpis['ejar_platform_fees'] ?? 0,
                'gateway_fee' => $kpis['gateway_fee'] ?? 0,
                'messaging_cost' => $kpis['messaging_cost'] ?? 0,
                'salaries_included' => (bool) ($kpis['salaries_included'] ?? false),
                'paid_contracts_count' => (int) ($kpis['paid_contracts_count'] ?? 0),
                'operating_profit_per_contract' => $kpis['operating_profit_per_contract'] ?? 0,
                'monthly_break_even_contracts' => (int) ($kpis['monthly_break_even_contracts'] ?? 0),
                'cac' => $kpis['cac'] ?? 0,
                'proration_days' => (int) ($kpis['proration_days'] ?? 0),
                'proration_month_days' => (int) ($kpis['proration_month_days'] ?? 30),
            ],
            'collected_breakdown' => $this->itemResolve(
                ReportCollectedBreakdownResource::class,
                $this->resource['collected_breakdown'] ?? []
            ),
            'service_revenue' => $this->collectResolve(
                ReportServiceRevenueResource::class,
                $this->resource['service_revenue'] ?? []
            ),
            'service_profitability' => $this->collectResolve(
                ReportServiceProfitabilityResource::class,
                $this->resource['service_profitability'] ?? []
            ),
            'unit_economics' => $this->collectResolve(
                ReportUnitEconomicsResource::class,
                $this->resource['unit_economics'] ?? []
            ),
            'source_summary' => $this->itemResolve(
                ReportSourceSummaryResource::class,
                $this->resource['source_summary'] ?? []
            ),
            'pnl' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['pnl'] ?? []
            ),
        ]);
    }
}
