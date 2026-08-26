<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class SalesReportResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kpis = is_array($this->resource['kpis'] ?? null) ? $this->resource['kpis'] : [];
        $summary = is_array($this->resource['summary'] ?? null) ? $this->resource['summary'] : [];

        return array_merge($this->periodFields(), [
            'kpis' => [
                'total_sales' => $kpis['total_sales'] ?? 0,
                'payments_count' => (int) ($kpis['payments_count'] ?? 0),
                'avg_order_value' => $kpis['avg_order_value'] ?? 0,
                'discounts_used' => $kpis['discounts_used'] ?? 0,
                'refunds' => $kpis['refunds'] ?? 0,
                'net_revenue' => $kpis['net_revenue'] ?? 0,
            ],
            'by_period' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['by_period'] ?? []
            ),
            'daily' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['daily'] ?? []
            ),
            'revenue_by_contract_type' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['revenue_by_contract_type'] ?? []
            ),
            'revenue_by_duration' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['revenue_by_duration'] ?? []
            ),
            'summary' => [
                'discounts_granted' => $summary['discounts_granted'] ?? 0,
                'discounted_orders_count' => (int) ($summary['discounted_orders_count'] ?? 0),
                'refunds_total' => $summary['refunds_total'] ?? 0,
                'refund_rate_percent' => (int) ($summary['refund_rate_percent'] ?? 0),
                'net_revenue_after_refunds' => $summary['net_revenue_after_refunds'] ?? 0,
            ],
        ]);
    }
}
