<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class CustomersReportResource extends ReportJsonResource
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
                'returning' => (int) ($kpis['returning'] ?? 0),
                'avg_contracts_per_customer' => $kpis['avg_contracts_per_customer'] ?? 0,
                'incomplete' => (int) ($kpis['incomplete'] ?? 0),
            ],
            'segments' => $this->collectResolve(
                ReportLabeledValueResource::class,
                $this->resource['segments'] ?? []
            ),
            'top_customers' => $this->collectResolve(
                ReportTopCustomerResource::class,
                $this->resource['top_customers'] ?? []
            ),
        ]);
    }
}
