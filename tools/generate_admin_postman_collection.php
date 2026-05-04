<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-API.postman_collection.json
 * Run: php tools/generate_admin_postman_collection.php
 */

$basePath = dirname(__DIR__);

function hdr(bool $bearer, bool $json = false): array
{
    $h = [];
    if ($bearer) {
        $h[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        $h[] = ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'];
    }

    return $h;
}

function req(string $name, string $method, string $path, array $opts = []): array
{
    $bearer = $opts['bearer'] ?? false;
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);

    $raw = '{{baseUrl}}/api/admin'.$path;
    if ($query !== []) {
        $raw .= '?'.http_build_query($query);
    }

    $request = [
        'method' => $method,
        'header' => hdr($bearer, $body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)),
        'url' => $raw,
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    return [
        'name' => $name,
        'request' => $request,
    ];
}

$folders = [
    'Employees (auth)' => [
        req('Login', 'POST', '/employees/login', ['body' => ['email' => 'admin@example.com', 'password' => 'password']]),
        req('Logout', 'POST', '/employees/logout', ['bearer' => true]),
        req('List employees', 'GET', '/employees', ['bearer' => true]),
        req('Create employee', 'POST', '/employees', ['bearer' => true, 'body' => ['email' => '', 'password' => '', 'name' => '']]),
        req('Show employee', 'GET', '/employees/{{employee_id}}', ['bearer' => true]),
        req('Update employee', 'POST', '/employees/{{employee_id}}', ['bearer' => true, 'body' => []]),
        req('Delete employee', 'POST', '/employees/{{employee_id}}/delete', ['bearer' => true]),
        req('Toggle status', 'POST', '/employees/{{employee_id}}/toggle-status', ['bearer' => true, 'body' => []]),
        req('Block', 'POST', '/employees/{{employee_id}}/block', ['bearer' => true, 'body' => []]),
        req('Unblock', 'POST', '/employees/{{employee_id}}/unblock', ['bearer' => true, 'body' => []]),
    ],
    'Analytics' => [
        req('Analytics', 'GET', '/analytics'),
        req('Dashboard analytics', 'GET', '/dashboard-analytics'),
    ],
    'Payments' => [
        req('List payments', 'GET', '/payments'),
        req('Show payment', 'GET', '/payments/{{payment_id}}'),
    ],
    'Finance — expenses' => [
        req('List expenses', 'GET', '/finance/expenses', ['query' => ['created_at' => 'month']]),
        req('Create expense', 'POST', '/finance/expenses', ['body' => ['amount' => 100, 'notes' => '', 'employee_id' => null]]),
        req('Show expense', 'GET', '/finance/expenses/{{expense_id}}'),
        req('Update expense', 'PUT', '/finance/expenses/{{expense_id}}', ['body' => ['amount' => 100, 'notes' => '']]),
        req('Delete expense', 'DELETE', '/finance/expenses/{{expense_id}}'),
    ],
    'Orders' => [
        req('List orders', 'GET', '/orders'),
        req('Incomplete orders', 'GET', '/orders/incomplete/list'),
        req('Complete orders', 'GET', '/orders/complete/list'),
        req('Filter orders', 'GET', '/orders/filter'),
        req('Show order', 'GET', '/orders/{{contract_id}}'),
        req('Update contract status', 'POST', '/orders/{{contract_id}}/contract-status', ['body' => []]),
    ],
    'Orders — comments (Bearer)' => [
        req('List comments', 'GET', '/orders/{{contract_id}}/comments', ['bearer' => true]),
        req('Create comment', 'POST', '/orders/{{contract_id}}/comments', ['bearer' => true, 'body' => ['body' => '']]),
        req('Update comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}', ['bearer' => true, 'body' => ['body' => '']]),
        req('Delete comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}/delete', ['bearer' => true]),
    ],
    'Users' => [
        req('All users', 'GET', '/users'),
        req('New users', 'GET', '/users/new'),
        req('Users complete contracts', 'GET', '/users/contracts-complete'),
        req('Block user', 'POST', '/users/{{user_id}}/block'),
        req('Delete user', 'POST', '/users/{{user_id}}/delete'),
    ],
    'Regions' => [
        req('List', 'GET', '/regions'),
        req('Create', 'POST', '/regions', ['body' => ['name_ar' => '', 'name_en' => '']]),
        req('Show', 'GET', '/regions/{{id}}'),
        req('Update', 'POST', '/regions/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/regions/{{id}}/delete'),
    ],
    'Cities' => [
        req('List', 'GET', '/cities'),
        req('Create', 'POST', '/cities', ['body' => []]),
        req('Show', 'GET', '/cities/{{id}}'),
        req('Update', 'POST', '/cities/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/cities/{{id}}/delete'),
    ],
    'Real estates' => [
        req('List', 'GET', '/real-estates'),
        req('Show', 'GET', '/real-estates/{{id}}'),
    ],
    'Real estate types' => [
        req('List', 'GET', '/real-estate-types'),
        req('Create', 'POST', '/real-estate-types', ['body' => []]),
        req('Update', 'POST', '/real-estate-types/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/real-estate-types/{{id}}/delete'),
    ],
    'Real estate usages' => [
        req('List', 'GET', '/real-estate-usages'),
        req('Create', 'POST', '/real-estate-usages', ['body' => []]),
        req('Show', 'GET', '/real-estate-usages/{{id}}'),
        req('Update', 'POST', '/real-estate-usages/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/real-estate-usages/{{id}}/delete'),
    ],
    'Unit real estates' => [
        req('List', 'GET', '/unit-real-estates'),
        req('Create', 'POST', '/unit-real-estates', ['body' => []]),
        req('Update', 'POST', '/unit-real-estates/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/unit-real-estates/{{id}}/delete'),
    ],
    'Unit types' => [
        req('Search', 'GET', '/unit-types/search'),
        req('Create form data', 'GET', '/unit-types/create'),
        req('List', 'GET', '/unit-types'),
        req('Create', 'POST', '/unit-types', ['body' => []]),
        req('Show', 'GET', '/unit-types/{{id}}'),
        req('Update', 'POST', '/unit-types/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/unit-types/{{id}}/delete'),
    ],
    'Unit usages' => [
        req('Create form data', 'GET', '/unit-usages/create'),
        req('List', 'GET', '/unit-usages'),
        req('Create', 'POST', '/unit-usages', ['body' => []]),
        req('Show', 'GET', '/unit-usages/{{id}}'),
        req('Update', 'POST', '/unit-usages/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/unit-usages/{{id}}/delete'),
    ],
    'Roles' => [
        req('Create form data', 'GET', '/roles/create'),
        req('List', 'GET', '/roles'),
        req('Create', 'POST', '/roles', ['body' => []]),
        req('Show', 'GET', '/roles/{{id}}'),
        req('Update', 'POST', '/roles/{{id}}', ['body' => []]),
        req('Assign permissions', 'POST', '/roles/{{id}}/assign-permissions', ['body' => ['permission_ids' => []]]),
        req('Delete', 'POST', '/roles/{{id}}/delete'),
    ],
    'Permissions' => [
        req('By section', 'GET', '/permissions/by-section'),
        req('Create form data', 'GET', '/permissions/create'),
        req('List', 'GET', '/permissions'),
        req('Create', 'POST', '/permissions', ['body' => ['section' => '', 'actions' => []]]),
        req('Show', 'GET', '/permissions/{{id}}'),
        req('Update', 'POST', '/permissions/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/permissions/{{id}}/delete'),
    ],
    'Contract statuses' => [
        req('Active', 'GET', '/contract-statuses/active'),
        req('List', 'GET', '/contract-statuses'),
        req('Create', 'POST', '/contract-statuses', ['body' => []]),
        req('Show', 'GET', '/contract-statuses/{{id}}'),
        req('Update', 'POST', '/contract-statuses/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/contract-statuses/{{id}}/delete'),
    ],
    'Contract periods' => [
        req('List', 'GET', '/contract-periods'),
        req('Create (helper)', 'POST', '/contract-periods/create', ['body' => []]),
        req('Create', 'POST', '/contract-periods', ['body' => []]),
        req('Show', 'GET', '/contract-periods/{{id}}'),
        req('Update', 'POST', '/contract-periods/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/contract-periods/{{id}}/delete'),
    ],
    'Contract WhatsApp' => [
        req('List', 'GET', '/contract-whatsapp'),
        req('Store complete', 'POST', '/contract-whatsapp/complete', ['body' => []]),
        req('Store incomplete', 'POST', '/contract-whatsapp/incomplete', ['body' => []]),
    ],
    'Coupons' => [
        req('List', 'GET', '/coupons'),
        req('Create', 'POST', '/coupons', ['body' => []]),
        req('Show', 'GET', '/coupons/{{id}}'),
        req('Update', 'POST', '/coupons/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/coupons/{{id}}/delete'),
    ],
    'Paperworks' => [
        req('List', 'GET', '/paperworks'),
        req('Create', 'POST', '/paperworks', ['body' => []]),
        req('Show', 'GET', '/paperworks/{{id}}'),
        req('Update', 'POST', '/paperworks/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/paperworks/{{id}}/delete'),
    ],
    'FAQs' => [
        req('List', 'GET', '/faqs'),
        req('Create', 'POST', '/faqs', ['body' => ['title_ar' => '', 'answer_ar' => '']]),
        req('Show', 'GET', '/faqs/{{id}}'),
        req('Update', 'POST', '/faqs/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/faqs/{{id}}/delete'),
    ],
    'Message alerts — sections' => [
        req('Options list (dropdown)', 'GET', '/message-alert-sections/options/list', ['query' => ['type' => 'client']]),
        req('List', 'GET', '/message-alert-sections', ['query' => ['type' => 'client', 'per_page' => '20']]),
        req('Create', 'POST', '/message-alert-sections', ['body' => ['name_ar' => '', 'name_en' => '', 'sort_order' => 0, 'type' => 'client']]),
        req('Show', 'GET', '/message-alert-sections/{{message_alert_section_id}}'),
        req('Update', 'POST', '/message-alert-sections/{{message_alert_section_id}}', ['body' => ['name_ar' => '']]),
        req('Delete', 'POST', '/message-alert-sections/{{message_alert_section_id}}/delete'),
        req('List items under section', 'GET', '/message-alert-sections/{{message_alert_section_id}}/items', ['query' => ['type' => 'client', 'per_page' => '20']]),
        req('Create item under section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/items', ['body' => ['name_ar' => '', 'name_en' => '', 'sort_order' => 0]]),
    ],
    'Message alerts — section items (flat)' => [
        req('Options list (dropdown)', 'GET', '/message-alert-section-items/options/list', ['query' => ['message_alert_section_id' => '{{message_alert_section_id}}', 'type' => 'client']]),
        req('List', 'GET', '/message-alert-section-items', ['query' => ['type' => 'client', 'message_alert_section_id' => '{{message_alert_section_id}}']]),
        req('Create', 'POST', '/message-alert-section-items', ['body' => ['message_alert_section_id' => 1, 'name_ar' => '', 'sort_order' => 0]]),
        req('Show', 'GET', '/message-alert-section-items/{{message_alert_section_item_id}}'),
        req('Update', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}', ['body' => []]),
        req('Delete', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}/delete'),
    ],
    'Message alerts (messages)' => [
        req('List', 'GET', '/message-alerts', ['query' => ['type' => 'client', 'message_alert_section_id' => '{{message_alert_section_id}}', 'message_alert_section_item_id' => '{{message_alert_section_item_id}}']]),
        req('Create', 'POST', '/message-alerts', ['body' => ['type' => 'client', 'message_alert_section_id' => 1, 'message_alert_section_item_id' => 1, 'message' => '']]),
        req('Show', 'GET', '/message-alerts/{{id}}', ['query' => ['type' => 'client']]),
        req('Update', 'POST', '/message-alerts/{{id}}', ['body' => ['message' => '', 'type' => 'client']]),
        req('Delete', 'POST', '/message-alerts/{{id}}/delete', ['body' => ['type' => 'client']]),
    ],
    'Blogs' => [
        req('List', 'GET', '/blogs'),
        req('Create', 'POST', '/blogs', ['body' => []]),
        req('Statistics', 'GET', '/blogs/statistics'),
        req('Show', 'GET', '/blogs/{{id}}'),
        req('Update', 'PUT', '/blogs/{{id}}', ['body' => []]),
        req('Delete', 'DELETE', '/blogs/{{id}}'),
        req('Toggle active', 'POST', '/blogs/{{id}}/toggle-active', ['body' => []]),
    ],
    'Content (terms & privacy)' => [
        req('Get terms', 'GET', '/content/terms-and-conditions'),
        req('Update terms', 'POST', '/content/terms-and-conditions', ['body' => []]),
        req('Get privacy', 'GET', '/content/privacy'),
        req('Update privacy', 'POST', '/content/privacy', ['body' => []]),
    ],
    'Ads' => [
        req('List', 'GET', '/ads'),
        req('Create', 'POST', '/ads', ['body' => []]),
        req('Show', 'GET', '/ads/{{id}}'),
        req('Update', 'POST', '/ads/{{id}}', ['body' => []]),
        req('Delete', 'POST', '/ads/{{id}}/delete'),
    ],
];

$items = [];
foreach ($folders as $folderName => $requests) {
    $items[] = [
        'name' => $folderName,
        'item' => $requests,
    ];
}

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-api-full',
        'name' => 'AQDI Admin API (full)',
        'description' => "All routes under `/api/admin` from `routes/admin.php`.\n\nSet collection variables:\n- **baseUrl** — e.g. `http://localhost:8000` or your API host\n- **employee_token** — Bearer token from `POST /api/admin/employees/login`\n\nRoutes using Sanctum: Employees (except login), Order comments.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'employee_id', 'value' => '1'],
        ['key' => 'contract_id', 'value' => '1'],
        ['key' => 'comment_id', 'value' => '1'],
        ['key' => 'user_id', 'value' => '1'],
        ['key' => 'payment_id', 'value' => '1'],
        ['key' => 'expense_id', 'value' => '1'],
        ['key' => 'id', 'value' => '1'],
        ['key' => 'message_alert_section_id', 'value' => '1'],
        ['key' => 'message_alert_section_item_id', 'value' => '1'],
    ],
    'item' => $items,
];

$out = $basePath.'/postman/AQDI-Admin-API.postman_collection.json';
if (! is_dir(dirname($out))) {
    mkdir(dirname($out), 0755, true);
}

file_put_contents(
    $out,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

echo "Wrote {$out}\n";
