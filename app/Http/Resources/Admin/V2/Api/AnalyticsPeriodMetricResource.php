<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsPeriodMetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource['key'],
            'label_ar' => $this->resource['label_ar'],
            'label_en' => $this->resource['label_en'] ?? null,
            'value' => $this->resource['value'],
            'type' => $this->resource['type'],
            'percentage_change' => $this->resource['percentage_change'] ?? null,
            'items' => $this->resource['items'] ?? [],
            'meta' => $this->resource['meta'] ?? null,
        ];
    }
}
