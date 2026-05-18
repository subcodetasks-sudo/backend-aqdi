<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsMetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $key = (string) ($this->resource['key'] ?? '');

        return [
            'key' => $key,
            'label_ar' => $this->resource['label_ar'] ?? null,
            'label_en' => $this->resource['label_en'] ?? null,
            'value' => $this->resource['value'],
            'type' => $this->resource['type'],
            'items' => $this->resource['items'] ?? [],
            'items_count' => is_array($this->resource['items'] ?? null)
                ? count($this->resource['items'])
                : 0,
            'meta' => $this->resource['meta'] ?? null,
        ];
    }
}
