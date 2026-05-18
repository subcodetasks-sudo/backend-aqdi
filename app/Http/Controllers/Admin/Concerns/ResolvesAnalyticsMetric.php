<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Http\Resources\Admin\V2\Api\AnalyticsMetricResource;
use InvalidArgumentException;

trait ResolvesAnalyticsMetric
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>|null  $meta
     */
    protected function analyticsMetric(
        string $key,
        mixed $value,
        array $items = [],
        ?array $meta = null,
        string $configFile = 'analytics_metrics'
    ) {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException('Analytics metric key must be an English slug.');
        }

        $definition = config("{$configFile}.{$key}");
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown analytics metric key: {$key}");
        }

        $payload = [
            'key' => $key,
            'label_ar' => $definition['label_ar'],
            'label_en' => $definition['label_en'],
            'value' => $value,
            'type' => $definition['type'],
            'items' => $items,
            'meta' => $meta,
        ];

        return $this->apiResponse(
            (new AnalyticsMetricResource($payload))->resolve(),
            trans('api.success')
        );
    }

    protected function analyticsListLimit(\Illuminate\Http\Request $request): int
    {
        return min(max((int) $request->input('limit', 10), 1), 100);
    }

    protected function topEmployeeDisplayValue(array $items): ?string
    {
        return $items[0]['name'] ?? null;
    }
}
