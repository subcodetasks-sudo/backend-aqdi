<?php

namespace App\Services\Marketing\Tracking;

use App\Models\AdSpendDaily;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingAttributionQueries
{
    public const PAID_SOURCES = ['google', 'meta', 'tiktok', 'snapchat', 'twitter'];

    public const CHANNELS = [
        'google' => ['ar' => 'قوقل', 'en' => 'Google', 'color' => 'blue'],
        'meta' => ['ar' => 'ميتا', 'en' => 'Meta', 'color' => 'purple'],
        'tiktok' => ['ar' => 'تيك توك', 'en' => 'TikTok', 'color' => 'black'],
        'snapchat' => ['ar' => 'سناب', 'en' => 'Snapchat', 'color' => 'yellow'],
        'twitter' => ['ar' => 'إكس', 'en' => 'X', 'color' => 'gray'],
    ];

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function contractBase(?array $range)
    {
        $query = Contract::query()->notDeleted()->reachedAdminOrderStep();
        $this->applyDateRange($query, 'contracts.created_at', $range);

        return $query;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function revenueQuery(?array $range)
    {
        $driver = DB::connection()->getDriverName();
        $query = Payment::query()
            ->successful()
            ->join('contracts', function ($join) use ($driver) {
                $join->on('payments.contract_uuid', '=', 'contracts.uuid');
                if ($driver === 'sqlite') {
                    $join->orWhereRaw("payments.contract_uuid LIKE contracts.uuid || '-%'");
                } else {
                    $join->orWhereRaw("payments.contract_uuid LIKE CONCAT(contracts.uuid, '-%')");
                }
            })
            ->where('contracts.is_delete', 0)
            ->where('contracts.step', '>=', 3);

        $this->applyDateRange($query, 'contracts.created_at', $range);

        return $query;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function campaignSpendQuery(?array $range)
    {
        $query = AdSpendDaily::query()->where(function ($q) {
            $q->where('keyword', '')->orWhereNull('keyword');
        });
        $this->applySpendRange($query, $range);

        return $query;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function keywordSpendQuery(?array $range)
    {
        $query = AdSpendDaily::query()
            ->whereNotNull('keyword')
            ->where('keyword', '!=', '');
        $this->applySpendRange($query, $range);

        return $query;
    }

    public function sourceExpression(string $table = 'contracts'): string
    {
        return "COALESCE(NULLIF({$table}.utm_source, ''), 'direct')";
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function previousRange(?array $range): ?array
    {
        if ($range === null) {
            return null;
        }

        $days = max(1, (int) $range[0]->diffInDays($range[1]) + 1);
        $to = $range[0]->copy()->subDay()->endOfDay();
        $from = $to->copy()->subDays($days - 1)->startOfDay();

        return [$from, $to];
    }

    public function money(float $amount): int|float
    {
        $rounded = round($amount, 2);

        return abs($rounded - (int) round($rounded)) < 0.005
            ? (int) round($rounded)
            : $rounded;
    }

    public function changePercent(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function channelMeta(string $source): array
    {
        $meta = self::CHANNELS[$source] ?? [
            'ar' => $source,
            'en' => $source,
            'color' => 'gray',
        ];

        return [
            'source' => $source,
            'label_ar' => $meta['ar'],
            'label_en' => $meta['en'],
            'color' => $meta['color'],
        ];
    }

    public function usersHavePlatform(): bool
    {
        try {
            return Schema::hasColumn('users', 'platform');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function applyDateRange($query, string $column, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween($column, [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()]);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    protected function applySpendRange($query, ?array $range): void
    {
        if ($range === null || ! Schema::hasTable('ad_spend_dailies')) {
            return;
        }

        $query->whereDate('spent_on', '>=', $range[0]->toDateString())
            ->whereDate('spent_on', '<=', $range[1]->toDateString());
    }
}
