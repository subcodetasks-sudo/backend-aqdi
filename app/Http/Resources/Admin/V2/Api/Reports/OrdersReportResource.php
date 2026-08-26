<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class OrdersReportResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kpis = is_array($this->resource['kpis'] ?? null) ? $this->resource['kpis'] : [];

        return array_merge($this->periodFields(), [
            'kpis' => [
                'total' => (int) ($kpis['total'] ?? 0),
                'new' => (int) ($kpis['new'] ?? 0),
                'paid' => (int) ($kpis['paid'] ?? 0),
                'draft' => (int) ($kpis['draft'] ?? 0),
                'incomplete' => (int) ($kpis['incomplete'] ?? 0),
                'canceled' => (int) ($kpis['canceled'] ?? 0),
                'returned' => (int) ($kpis['returned'] ?? 0),
                'avg_completion_minutes' => $kpis['avg_completion_minutes'] ?? null,
            ],
            'by_employee' => $this->collectResolve(
                ReportEmployeeStatResource::class,
                $this->resource['by_employee'] ?? []
            ),
            'by_contract_type' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['by_contract_type'] ?? []
            ),
            'by_stage' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['by_stage'] ?? []
            ),
        ]);
    }
}
