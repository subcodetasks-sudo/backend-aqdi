<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class ReportProfitSettingsResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'moyasar_fee_percent' => $this->resource['moyasar_fee_percent'] ?? null,
            'moyasar_mada_percent' => $this->resource['moyasar_mada_percent'] ?? null,
            'moyasar_credit_percent' => $this->resource['moyasar_credit_percent'] ?? null,
            'moyasar_fixed_fee' => $this->resource['moyasar_fixed_fee'] ?? null,
            'operating_budget' => $this->resource['operating_budget'] ?? null,
            'marketing_budget' => $this->resource['marketing_budget'] ?? null,
            'meter_transfer_fee' => $this->resource['meter_transfer_fee'] ?? null,
            'proration_month_days' => (int) ($this->resource['proration_month_days'] ?? 30),
        ];

        if (array_key_exists('monthly_salaries', $this->resource)) {
            $payload['monthly_salaries'] = $this->resource['monthly_salaries'];
        }

        return $payload;
    }
}
