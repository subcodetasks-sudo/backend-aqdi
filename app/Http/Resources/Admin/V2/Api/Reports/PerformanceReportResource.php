<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class PerformanceReportResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kpis = is_array($this->resource['kpis'] ?? null) ? $this->resource['kpis'] : [];
        $leakage = is_array($this->resource['conversion_leakage'] ?? null)
            ? $this->resource['conversion_leakage']
            : [];

        return array_merge($this->periodFields(), [
            'period_label' => $this->resource['period_label'] ?? null,
            'kpis' => [
                'total_count' => (int) ($kpis['total_count'] ?? $kpis['total'] ?? 0),
                'total' => (int) ($kpis['total'] ?? $kpis['total_count'] ?? 0),
                'documented_count' => (int) ($kpis['documented_count'] ?? 0),
                'working_count' => (int) ($kpis['working_count'] ?? $kpis['active_count'] ?? 0),
                'active_count' => (int) ($kpis['active_count'] ?? $kpis['working_count'] ?? 0),
                'canceled_count' => (int) ($kpis['canceled_count'] ?? 0),
                'refunded_count' => (int) ($kpis['refunded_count'] ?? 0),
                'revenue' => $kpis['revenue'] ?? 0,
                'paid' => (int) ($kpis['paid'] ?? 0),
                'delayed_count' => (int) ($kpis['delayed_count'] ?? 0),
            ],
            'conversion_funnel' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['conversion_funnel'] ?? []
            ),
            'conversion_leakage' => [
                'count' => (int) ($leakage['count'] ?? 0),
                'percent' => (int) ($leakage['percent'] ?? 0),
            ],
            'conversion_rates' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['conversion_rates'] ?? []
            ),
            'daily_orders' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['daily_orders'] ?? []
            ),
            'orders_by_status' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['orders_by_status'] ?? []
            ),
            'by_contract_type' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['by_contract_type'] ?? []
            ),
            'by_employee' => $this->collectResolve(
                ReportEmployeeStatResource::class,
                $this->resource['by_employee'] ?? []
            ),
            'operational_metrics' => $this->itemResolve(
                ReportOperationalMetricsResource::class,
                $this->resource['operational_metrics'] ?? []
            ),
            'revenue_by_payment_method' => $this->collectResolve(
                ReportPaymentMethodResource::class,
                $this->resource['revenue_by_payment_method'] ?? []
            ),
            'pnl' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['pnl'] ?? []
            ),
            'unit_economics' => $this->collectResolve(
                ReportUnitEconomicsResource::class,
                $this->resource['unit_economics'] ?? []
            ),
            'unit_economics_note' => $this->resource['unit_economics_note'] ?? null,
            'financial_summary' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['financial_summary'] ?? []
            ),
            'by_document_type' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['by_document_type'] ?? []
            ),
            'correction_errors' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['correction_errors'] ?? []
            ),
            'refund_requests_by_status' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['refund_requests_by_status'] ?? []
            ),
            'refund_requests_total' => $this->resource['refund_requests_total'] ?? 0,
        ]);
    }
}
