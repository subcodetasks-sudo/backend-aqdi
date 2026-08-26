<?php

namespace App\Services\Seo;

use App\Models\SeoCrawlIssue;
use App\Models\SeoCrawlPage;
use App\Models\SeoCrawlRun;
use App\Support\SeoCrawlIssueType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeoCrawlService
{
    public function __construct(
        protected SiteCrawler $crawler,
        protected SiteAuditAnalyzer $analyzer,
        protected SeoCrawlFirebaseStatus $firebaseStatus
    ) {}

    public function createRun(?string $baseUrl = null): SeoCrawlRun
    {
        $run = SeoCrawlRun::query()->create([
            'base_url' => $this->crawler->normalizeUrl($baseUrl ?: (string) config('seo_crawl.base_url')),
            'status' => SeoCrawlRun::STATUS_QUEUED,
        ]);

        $this->firebaseStatus->publish($run);

        return $run;
    }

    public function inProgressRun(): ?SeoCrawlRun
    {
        return SeoCrawlRun::query()
            ->whereIn('status', [SeoCrawlRun::STATUS_QUEUED, SeoCrawlRun::STATUS_RUNNING])
            ->latest('id')
            ->first();
    }

    public function latestRun(): ?SeoCrawlRun
    {
        return SeoCrawlRun::query()->latest('id')->first();
    }

    public function requestStop(int $runId): void
    {
        Cache::put($this->stopCacheKey($runId), true, now()->addHours(2));
    }

    public function isStopRequested(int $runId): bool
    {
        return (bool) Cache::get($this->stopCacheKey($runId));
    }

    public function stop(?int $runId = null): ?SeoCrawlRun
    {
        $run = $runId
            ? SeoCrawlRun::query()->find($runId)
            : $this->inProgressRun();

        if (! $run) {
            return null;
        }

        if (! $run->isInProgress()) {
            return null;
        }

        $this->requestStop($run->id);
        $run->update([
            'status' => SeoCrawlRun::STATUS_STOPPED,
            'finished_at' => now(),
        ]);

        $run = $run->refresh();
        $this->firebaseStatus->publish($run);

        return $run;
    }

    public function execute(int $runId, ?int $maxPages = null, ?callable $onProgress = null): SeoCrawlRun
    {
        $run = SeoCrawlRun::query()->findOrFail($runId);

        if ($this->isStopRequested($runId) || $run->status === SeoCrawlRun::STATUS_STOPPED) {
            $run->update([
                'status' => SeoCrawlRun::STATUS_STOPPED,
                'finished_at' => $run->finished_at ?? now(),
            ]);
            $this->clearStopRequest($runId);
            $run = $run->refresh();
            $this->firebaseStatus->publish($run);

            return $run;
        }

        $run->update([
            'status' => SeoCrawlRun::STATUS_RUNNING,
            'started_at' => now(),
            'error_message' => null,
        ]);
        $run = $run->refresh();
        $this->firebaseStatus->publish($run);

        $progress = function (string $url, int $n, int $max) use ($onProgress, $run): void {
            $this->firebaseStatus->progress($run, $url, $n, $max);
            if ($onProgress) {
                $onProgress($url, $n, $max);
            }
        };

        try {
            $pages = $this->crawler->crawl(
                $run->base_url,
                $maxPages,
                $progress,
                fn () => $this->isStopRequested($runId)
            );

            return $this->persistResults($run, $pages, $this->isStopRequested($runId));
        } catch (SeoCrawlStoppedException $e) {
            return $this->persistResults($run, $e->pages, true);
        } catch (Throwable $e) {
            $run->update([
                'status' => SeoCrawlRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            $this->clearStopRequest($runId);
            $this->firebaseStatus->publish($run->refresh());

            throw $e;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    protected function persistResults(SeoCrawlRun $run, array $pages, bool $stopped): SeoCrawlRun
    {
        $stopped = $stopped || $this->isStopRequested($run->id) || $run->refresh()->status === SeoCrawlRun::STATUS_STOPPED;

        if ($pages === []) {
            $run->update([
                'status' => $stopped ? SeoCrawlRun::STATUS_STOPPED : SeoCrawlRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'indexed_pages' => 0,
                'healthy_pages' => 0,
                'broken_pages' => 0,
                'on_page_issues' => 0,
                'pages_crawled' => 0,
                'pages_failed' => 0,
                'category_counts' => null,
            ]);
            $this->clearStopRequest($run->id);

            $run = $run->refresh();
            $this->firebaseStatus->publish($run);

            return $run;
        }

        $analysis = $this->analyzer->analyze(
            $pages,
            (int) config('seo_crawl.slow_page_ms', 3000),
            (int) config('seo_crawl.weak_inbound_links', 2)
        );
        $summary = $this->analyzer->summarize($pages, $analysis['issues'], $analysis['inbound']);
        $issueKeys = [];
        foreach ($analysis['issues'] as $issue) {
            $issueKeys[$issue['page_key']] = true;
        }

        DB::transaction(function () use ($run, $pages, $analysis, $summary, $issueKeys, $stopped): void {
            $run->pages()->delete();
            $run->issues()->delete();

            $pageIds = [];
            foreach ($pages as $page) {
                $key = $this->analyzer->pageKey($page);
                $record = SeoCrawlPage::query()->create([
                    'seo_crawl_run_id' => $run->id,
                    'url_hash' => $key,
                    'url' => $page['url'],
                    'path' => $page['path'],
                    'status_code' => $page['status_code'],
                    'load_time_ms' => $page['load_time_ms'],
                    'content_type' => $page['content_type'],
                    'title' => $page['title'],
                    'meta_description' => $page['meta_description'],
                    'h1_count' => $page['h1_count'],
                    'image_count' => $page['image_count'],
                    'images_missing_alt' => $page['images_missing_alt'],
                    'outbound_internal_links' => count($page['outbound_urls'] ?? []),
                    'inbound_internal_links' => $analysis['inbound'][$key] ?? 0,
                    'is_html' => (bool) $page['is_html'],
                    'is_indexable' => (bool) $page['is_indexable'],
                    'is_healthy' => $this->analyzer->isIndexed($page) && empty($issueKeys[$key]),
                ]);
                $pageIds[$key] = $record->id;
            }

            foreach ($analysis['issues'] as $issue) {
                SeoCrawlIssue::query()->create([
                    'seo_crawl_run_id' => $run->id,
                    'seo_crawl_page_id' => $pageIds[$issue['page_key']] ?? null,
                    'path' => $issue['path'],
                    'type' => $issue['type'],
                    'severity' => $issue['severity'],
                    'message_ar' => $issue['message_ar'],
                    'message_en' => $issue['message_en'],
                    'details' => $issue['details'],
                ]);
            }

            $run->update([
                'status' => $stopped ? SeoCrawlRun::STATUS_STOPPED : SeoCrawlRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'indexed_pages' => $summary['indexed_pages'],
                'healthy_pages' => $summary['healthy_pages'],
                'broken_pages' => $summary['broken_pages'],
                'on_page_issues' => $summary['on_page_issues'],
                'pages_crawled' => $summary['pages_crawled'],
                'pages_failed' => $summary['pages_failed'],
                'category_counts' => $summary['category_counts'],
                'error_message' => null,
            ]);
        });

        $this->clearStopRequest($run->id);

        $run = $run->refresh();
        $this->firebaseStatus->publish($run);

        return $run;
    }

    protected function stopCacheKey(int $runId): string
    {
        return 'seo-crawl:stop:'.$runId;
    }

    protected function clearStopRequest(int $runId): void
    {
        Cache::forget($this->stopCacheKey($runId));
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?SeoCrawlRun $run = null): array
    {
        $run ??= $this->latestRun();
        $site = parse_url((string) config('seo_crawl.base_url'), PHP_URL_HOST) ?: 'aqdi.sa';

        $counts = $run?->category_counts ?? [];
        $categories = [];
        foreach (SeoCrawlIssueType::dashboardOrder() as $type) {
            $severity = $type === 'healthy_pages'
                ? 'success'
                : (SeoCrawlIssueType::tryFrom($type)?->severity() ?? 'low');

            $categories[] = [
                'type' => $type,
                'label' => trans('seo_crawl.categories.'.$type),
                'label_ar' => trans('seo_crawl.categories.'.$type, [], 'ar'),
                'label_en' => trans('seo_crawl.categories.'.$type, [], 'en'),
                'count' => (int) ($counts[$type] ?? ($type === 'healthy_pages' ? ($run?->healthy_pages ?? 0) : 0)),
                'severity' => $severity,
            ];
        }

        return [
            'id' => $run?->id,
            'site' => $site,
            'title' => trans('seo_crawl.title'),
            'description' => trans('seo_crawl.description'),
            'status' => $run?->status ?? 'never_run',
            'last_scanned_at' => $run?->finished_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'last_scanned_at_iso' => $run?->finished_at?->toIso8601String(),
            'base_url' => $run?->base_url ?? (string) config('seo_crawl.base_url'),
            'pages_crawled' => (int) ($run?->pages_crawled ?? 0),
            'pages_failed' => (int) ($run?->pages_failed ?? 0),
            'error_message' => $run?->error_message,
            'realtime' => [
                'enabled' => (bool) config('seo_crawl.firebase_status', true)
                    && filled(config('services.firebase.database_url')),
                'path' => (string) config('seo_crawl.firebase_path', 'seo_crawl/status'),
            ],
            'summary' => [
                'indexed_pages' => [
                    'label' => trans('seo_crawl.indexed_pages'),
                    'count' => (int) ($run?->indexed_pages ?? 0),
                ],
                'healthy_pages' => [
                    'label' => trans('seo_crawl.healthy_pages'),
                    'count' => (int) ($run?->healthy_pages ?? 0),
                ],
                'broken_pages' => [
                    'label' => trans('seo_crawl.broken_pages'),
                    'count' => (int) ($run?->broken_pages ?? 0),
                ],
                'on_page_issues' => [
                    'label' => trans('seo_crawl.on_page_issues'),
                    'count' => (int) ($run?->on_page_issues ?? 0),
                ],
            ],
            'categories' => $categories,
        ];
    }

    public function paginateIssues(SeoCrawlRun $run, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $run->issues()->latest('id');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($inner) use ($search): void {
                $inner->where('path', 'like', '%'.$search.'%')
                    ->orWhere('message_ar', 'like', '%'.$search.'%')
                    ->orWhere('message_en', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage);
    }
}
