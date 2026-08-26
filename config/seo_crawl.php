<?php

return [
    'base_url' => env('SEO_CRAWL_BASE_URL', 'https://aqdi.sa'),

    'user_agent' => env('SEO_CRAWL_USER_AGENT', 'AqdiSeoBot/1.0 (+https://aqdi.sa)'),

    'max_pages' => (int) env('SEO_CRAWL_MAX_PAGES', 400),

    'timeout_seconds' => (int) env('SEO_CRAWL_TIMEOUT', 20),

    'delay_ms' => (int) env('SEO_CRAWL_DELAY_MS', 80),

    /** Pages slower than this (milliseconds) are flagged as slow. */
    'slow_page_ms' => (int) env('SEO_CRAWL_SLOW_MS', 3000),

    /** HTML pages with fewer inbound internal links than this are "weak". Home is exempt. */
    'weak_inbound_links' => (int) env('SEO_CRAWL_WEAK_INBOUND', 2),

    'allowed_hosts' => [
        'aqdi.sa',
        'www.aqdi.sa',
    ],

    'exclude_path_regex' => [
        '#^/(login|signup|logout|forget_password|new_password|reset_password|send_code|verification|resend-verification)(/|$)#i',
        '#^/contract(/|$)#i',
        '#^/(work-paper|financial_statements)/#i',
        '#^/pricing/[A-Za-z0-9\-]+#i',
        '#^/(real|unit|choose|coupon|check)(/|$)#i',
        '#^/(api|admin|telescope|livewire|storage)(/|$)#i',
        '#^/db(/|$)#i',
    ],

    'skip_extensions' => [
        'pdf', 'zip', 'rar', '7z', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'mp3', 'mp4', 'avi', 'mov', 'wmv', 'webp', 'jpg', 'jpeg', 'png', 'gif',
        'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'css', 'js', 'map', 'xml',
        'json', 'txt', 'csv',
    ],
];
