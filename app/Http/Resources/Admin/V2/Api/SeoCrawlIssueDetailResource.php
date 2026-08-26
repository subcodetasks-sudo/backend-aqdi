<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;

class SeoCrawlIssueDetailResource extends SeoCrawlIssueResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $issue = parent::toArray($request);
        $page = $this->resource->relationLoaded('page') ? $this->page : null;

        return [
            ...$issue,
            'run_id' => (int) $this->seo_crawl_run_id,
            'page_details' => $page ? [
                'id' => (int) $page->id,
                'url' => $page->url,
                'path' => $page->path,
                'status_code' => (int) $page->status_code,
                'load_time_ms' => (int) $page->load_time_ms,
                'content_type' => $page->content_type,
                'title' => $page->title,
                'meta_description' => $page->meta_description,
                'h1_count' => (int) $page->h1_count,
                'image_count' => (int) $page->image_count,
                'images_missing_alt' => (int) $page->images_missing_alt,
                'outbound_internal_links' => (int) $page->outbound_internal_links,
                'inbound_internal_links' => (int) $page->inbound_internal_links,
                'is_html' => (bool) $page->is_html,
                'is_indexable' => (bool) $page->is_indexable,
                'is_healthy' => (bool) $page->is_healthy,
            ] : null,
        ];
    }
}
