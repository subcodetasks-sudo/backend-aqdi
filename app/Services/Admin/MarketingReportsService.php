<?php

namespace App\Services\Admin;

use App\Models\AdSpendDaily;
use App\Models\Contract;
use App\Models\Payment;
use App\Support\Concerns\ResolvesReportPeriod;
use App\Support\Marketing\UtmAttribution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingReportsService
{
    use ResolvesReportPeriod;

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter): array
    {
        $range = $filter['range'];
        $bySource = $this->bySource($range);

        return [
            'accounts' => app(\App\Services\Marketing\AdSpendSyncService::class)->credentialStatus(),
            'by_source' => $bySource,
            'orders_by_source' => array_map(static fn (array $row) => [
                'source' => $row['source'],
                'label' => $row['label'],
                'orders' => $row['orders'],
                'paid' => $row['paid'],
                'revenue' => $row['revenue'],
            ], $bySource),
            'top_keywords' => $this->topKeywords($range),
            'weakest_campaigns' => $this->weakestCampaigns($range),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function utmTemplate(): array
    {
        $sources = [];
        foreach (config('ads.utm.sources', []) as $key => $labels) {
            $sources[] = [
                'source' => $key,
                'label_ar' => $labels['ar'] ?? $key,
                'label_en' => $labels['en'] ?? $key,
            ];
        }

        $exampleQuery = UtmAttribution::buildQuery(
            'google',
            'search-lease',
            'عقد إيجار إلكتروني',
            'adgroup-1'
        );

        return [
            'template' => '?utm_source={source}&utm_medium=cpc&utm_campaign={campaign}&utm_term={keyword}&utm_content={adset}',
            'example' => url('/').'/?'.$exampleQuery,
            'whatsapp_example' => 'https://wa.me/966000000000?text='.rawurlencode('مرحبا').'&'.$exampleQuery,
            'click_ids' => array_keys(config('ads.utm.click_ids', [])),
            'sources' => $sources,
            'accounts' => app(\App\Services\Marketing\AdSpendSyncService::class)->credentialStatus(),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    private function bySource(?array $range): array
    {
        $orderRows = $this->contractBase($range)
            ->selectRaw($this->sourceExpression().' as source')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as paid')
            ->groupByRaw($this->sourceExpression())
            ->get()
            ->keyBy('source');

        $revenueRows = $this->revenueQuery($range)
            ->selectRaw($this->sourceExpression('contracts').' as source')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->groupByRaw($this->sourceExpression('contracts'))
            ->pluck('revenue', 'source');

        $spendRows = $this->campaignSpendQuery($range)
            ->select('platform')
            ->selectRaw('COALESCE(SUM(spend), 0) as total_spend')
            ->groupBy('platform')
            ->pluck('total_spend', 'platform');

        $sources = array_keys(config('ads.utm.sources', []));
        $seen = array_unique([
            ...$sources,
            ...$orderRows->keys()->all(),
            ...$revenueRows->keys()->all(),
            ...$spendRows->keys()->all(),
        ]);

        $items = [];
        foreach ($seen as $source) {
            $source = (string) $source;
            $orders = (int) ($orderRows[$source]->orders ?? 0);
            $paid = (int) ($orderRows[$source]->paid ?? 0);
            $revenue = $this->moneyValue((float) ($revenueRows[$source] ?? 0));
            $spend = $this->moneyValue((float) ($spendRows[$source] ?? 0));
            $conversion = $orders > 0 ? (int) round(($paid / $orders) * 100) : 0;

            $items[] = [
                'source' => $source,
                'label' => UtmAttribution::sourceLabel($source),
                'orders' => $orders,
                'paid' => $paid,
                'revenue' => $revenue,
                'spend' => $spend,
                'cac' => ($spend > 0 && $paid > 0) ? $this->moneyValue($spend / $paid) : null,
                'conversion_percent' => $conversion,
            ];
        }

        usort($items, static fn (array $a, array $b) => $b['orders'] <=> $a['orders']);

        return array_values($items);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{keyword: string, revenue: int|float, orders: int}>
     */
    private function topKeywords(?array $range): array
    {
        return $this->revenueQuery($range)
            ->whereNotNull('contracts.utm_term')
            ->where('contracts.utm_term', '!=', '')
            ->selectRaw('contracts.utm_term as keyword')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT contracts.id) as orders')
            ->groupBy('contracts.utm_term')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'keyword' => (string) $row->keyword,
                'revenue' => $this->moneyValue((float) $row->revenue),
                'orders' => (int) $row->orders,
            ])
            ->all();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    private function weakestCampaigns(?array $range): array
    {
        $spend = $this->campaignSpendQuery($range)
            ->select('platform', 'campaign_name')
            ->selectRaw('COALESCE(SUM(spend), 0) as total_spend')
            ->where('campaign_name', '!=', '')
            ->whereNotNull('campaign_name')
            ->groupBy('platform', 'campaign_name')
            ->get();

        $revenue = $this->revenueQuery($range)
            ->whereNotNull('contracts.utm_campaign')
            ->where('contracts.utm_campaign', '!=', '')
            ->selectRaw('contracts.utm_campaign as campaign_name')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->groupBy('contracts.utm_campaign')
            ->pluck('revenue', 'campaign_name');

        $campaigns = [];
        foreach ($spend as $row) {
            $name = (string) $row->campaign_name;
            $spendValue = (float) $row->total_spend;
            if ($spendValue <= 0) {
                continue;
            }
            $revenueValue = (float) ($revenue[$name] ?? 0);
            $profit = $revenueValue - $spendValue;
            $roas = $spendValue > 0 ? round($revenueValue / $spendValue, 2) : null;

            $campaigns[] = [
                'platform' => (string) $row->platform,
                'platform_label' => UtmAttribution::sourceLabel((string) $row->platform),
                'campaign' => $name,
                'spend' => $this->moneyValue($spendValue),
                'revenue' => $this->moneyValue($revenueValue),
                'profit' => $this->moneyValue($profit),
                'roas' => $roas,
            ];
        }

        usort($campaigns, static function (array $a, array $b) {
            $aRoas = $a['roas'] ?? PHP_INT_MAX;
            $bRoas = $b['roas'] ?? PHP_INT_MAX;

            return $aRoas <=> $bRoas;
        });

        return array_slice(array_values($campaigns), 0, 10);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function contractBase(?array $range)
    {
        $query = Contract::query()->notDeleted()->reachedAdminOrderStep();
        $this->applyDateRange($query, 'contracts.created_at', $range);

        return $query;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function revenueQuery(?array $range)
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
    private function campaignSpendQuery(?array $range)
    {
        $query = AdSpendDaily::query()->where(function ($q) {
            $q->where('keyword', '')->orWhereNull('keyword');
        });
        if ($range !== null && Schema::hasTable('ad_spend_dailies')) {
            $query->whereDate('spent_on', '>=', $range[0]->toDateString())
                ->whereDate('spent_on', '<=', $range[1]->toDateString());
        }

        return $query;
    }

    private function sourceExpression(string $table = 'contracts'): string
    {
        return "COALESCE(NULLIF({$table}.utm_source, ''), 'direct')";
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function applyDateRange($query, string $column, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween($column, [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()]);
    }

    private function moneyValue(float $amount): int|float
    {
        $rounded = round($amount, 2);

        return abs($rounded - (int) round($rounded)) < 0.005
            ? (int) round($rounded)
            : $rounded;
    }
}
