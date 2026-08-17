<?php

/**
 * Canonical Admin Orders + Status collection (no duplicate list URLs).
 * Run: php postman/generate_orders_status_collection.php
 */

$out = __DIR__.'/AQDI-Admin-Orders-And-Status.postman_collection.json';

$authHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
];

$jsonHeaders = array_merge($authHeaders, [
    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
]);

$makeGet = static function (string $name, string $path, array $query, string $description) use ($authHeaders): array {
    $queryItems = [];
    $enabled = [];
    foreach ($query as $row) {
        $item = [
            'key' => $row['key'],
            'value' => (string) $row['value'],
        ];
        if (! empty($row['disabled'])) {
            $item['disabled'] = true;
        } else {
            $enabled[] = $row['key'].'='.$row['value'];
        }
        $queryItems[] = $item;
    }

    $raw = '{{baseUrl}}/'.$path.($enabled !== [] ? ('?'.implode('&', $enabled)) : '');
    $parts = array_values(array_filter(explode('/', $path), static fn ($p) => $p !== ''));

    return [
        'name' => $name,
        'request' => [
            'method' => 'GET',
            'header' => $authHeaders,
            'url' => [
                'raw' => $raw,
                'host' => ['{{baseUrl}}'],
                'path' => $parts,
                'query' => $queryItems,
            ],
            'description' => $description,
        ],
        'response' => [],
    ];
};

$makePost = static function (string $name, string $path, array $body, string $description) use ($jsonHeaders): array {
    $parts = array_values(array_filter(explode('/', $path), static fn ($p) => $p !== ''));

    return [
        'name' => $name,
        'request' => [
            'method' => 'POST',
            'header' => $jsonHeaders,
            'url' => [
                'raw' => '{{baseUrl}}/'.$path,
                'host' => ['{{baseUrl}}'],
                'path' => $parts,
            ],
            'description' => $description,
            'body' => [
                'mode' => 'raw',
                'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'options' => ['raw' => ['language' => 'json']],
            ],
        ],
        'response' => [],
    ];
};

$login = [
    'name' => 'Employee login',
    'event' => [[
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'try {',
                '  const json = pm.response.json();',
                '  const token = json?.data?.token || json?.data?.access_token || json?.token;',
                '  if (token) { pm.collectionVariables.set("employee_token", token); }',
                '} catch (e) {}',
            ],
        ],
    ]],
    'request' => [
        'method' => 'POST',
        'header' => [
            ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
        ],
        'url' => [
            'raw' => '{{baseUrl}}/api/admin/employees/login',
            'host' => ['{{baseUrl}}'],
            'path' => ['api', 'admin', 'employees', 'login'],
        ],
        'body' => [
            'mode' => 'raw',
            'raw' => json_encode([
                'email' => 'mohammed@aqdi.com',
                'password' => 'password',
                'fcm_token' => '{{fcm_token}}',
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ],
        'description' => 'Saves employee_token automatically.',
    ],
    'response' => [],
];

$commonQuery = [
    ['key' => 'page', 'value' => '1'],
    ['key' => 'per_page', 'value' => '20'],
    ['key' => 'search', 'value' => '', 'disabled' => true],
    ['key' => 'contract_type', 'value' => 'housing', 'disabled' => true],
];

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-orders-status-2026-08-17',
        'name' => 'AQDI Admin — Orders & Status',
        'description' => <<<'TXT'
Canonical Admin APIs for **orders + status** only. One list URL. Status is always a query param.

## List
`GET /api/admin/orders`

| Query | Meaning |
|---|---|
| `status_id` | Contract status (1 جديد, 6 مستلم, 2 مسترجع, 8 واتساب, 9 إيجار, 10 مشرف) |
| `is_draft=1` | Drafts; `status_id` then filters **draft** statuses |
| `complete=1` / `incomplete=1` | Paid / unpaid |
| `is_received=0` | New, not received yet |
| `is_received=1` | Has a received_contracts row |
| `return=1` | Return orders |
| `return_status` | pending / accept / reject |

Do **not** use `/orders/status/{id}` or `/orders/draft/status/{id}` — same data via query params.

## Status update
`POST /api/admin/orders/{id}/status`  body `{ "status_id": 9, ...case fields }`
TXT,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
        ['key' => 'id', 'value' => '1'],
        ['key' => 'contract_id', 'value' => '1'],
        ['key' => 'status_id', 'value' => '1'],
        ['key' => 'comment_id', 'value' => '1'],
        ['key' => 'unit_id', 'value' => '1'],
        ['key' => 'user_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => '01 Auth',
            'item' => [$login],
        ],
        [
            'name' => '02 Status catalog',
            'item' => [
                $makeGet('Active contract statuses', 'api/admin/contract-statuses/active', [], 'Use these ids in ?status_id='),
                $makeGet('All contract statuses', 'api/admin/contract-statuses', [['key' => 'per_page', 'value' => '50']], ''),
                $makePost('Create contract status', 'api/admin/contract-statuses', [
                    'name' => 'حالة جديدة',
                    'color' => '#3B82F6',
                    'color_text' => '#FFFFFF',
                    'description' => 'وصف داخلي',
                    'client_explanation' => 'شرح للعميل',
                    'is_active' => true,
                ], ''),
                $makePost('Update contract status', 'api/admin/contract-statuses/{{status_id}}', [
                    'name' => 'حالة محدثة',
                    'color' => '#22C55E',
                    'client_explanation' => 'شرح محدث',
                    'is_active' => true,
                ], ''),
                $makeGet('Active draft statuses', 'api/admin/draft-contract-statuses/active', [], 'Use with ?is_draft=1&status_id='),
                $makeGet('All draft statuses', 'api/admin/draft-contract-statuses', [['key' => 'per_page', 'value' => '50']], ''),
            ],
        ],
        [
            'name' => '03 Orders list (query params only)',
            'item' => [
                $makeGet('By status_id (new = 1)', 'api/admin/orders', array_merge([
                    ['key' => 'status_id', 'value' => '1'],
                ], $commonQuery), "New orders. Response includes summary cards:\nإجمالي الطلبات الجديدة / تجاوزت 15 دقيقة / تجاوزت 30 دقيقة"),
                $makeGet('By status_id (received = 6)', 'api/admin/orders', array_merge([
                    ['key' => 'status_id', 'value' => '6'],
                ], $commonQuery), ''),
                $makeGet('By status_id (return = 2)', 'api/admin/orders', array_merge([
                    ['key' => 'status_id', 'value' => '2'],
                ], $commonQuery), ''),
                $makeGet('Drafts', 'api/admin/orders', array_merge([
                    ['key' => 'is_draft', 'value' => '1'],
                ], $commonQuery), 'GET /api/admin/orders?is_draft=1'),
                $makeGet('Drafts by status_id', 'api/admin/orders', array_merge([
                    ['key' => 'is_draft', 'value' => '1'],
                    ['key' => 'status_id', 'value' => '{{status_id}}'],
                ], $commonQuery), 'status_id here is draft_contract_statuses.id'),
                $makeGet('Completed', 'api/admin/orders', array_merge([
                    ['key' => 'complete', 'value' => '1'],
                ], $commonQuery), 'GET /api/admin/orders?complete=1'),
                $makeGet('Incomplete', 'api/admin/orders', array_merge([
                    ['key' => 'incomplete', 'value' => '1'],
                ], $commonQuery), 'GET /api/admin/orders?incomplete=1'),
                $makeGet('Not received yet', 'api/admin/orders', array_merge([
                    ['key' => 'is_received', 'value' => '0'],
                ], $commonQuery), 'New contracts waiting to be received'),
                $makeGet('Received (has received_contracts row)', 'api/admin/orders', array_merge([
                    ['key' => 'is_received', 'value' => '1'],
                ], $commonQuery), ''),
                $makeGet('Return orders', 'api/admin/orders', array_merge([
                    ['key' => 'return', 'value' => '1'],
                    ['key' => 'return_status', 'value' => 'pending', 'disabled' => true],
                ], $commonQuery), 'return_status=pending|accept|reject'),
            ],
        ],
        [
            'name' => '04 Order details & update',
            'item' => [
                $makeGet('Show order (full detail)', 'api/admin/orders/{{id}}', [], 'Single response: all contract fields, comments, invoice, timeline, received_since, receive_speed.'),
                $makePost('Update order fields', 'api/admin/orders/{{id}}', [
                    'notes_edits' => 'تعديل ملاحظات',
                ], 'Send only fields to change.'),
            ],
        ],
        [
            'name' => '05 Update order status (one endpoint)',
            'item' => [
                $makePost('Change status', 'api/admin/orders/{{id}}/status', [
                    'status_id' => '{{status_id}}',
                ], 'One API for contract and draft. Auto-detects is_draft from the order.'),
                $makePost('Status 9 — Ejar authentication', 'api/admin/orders/{{id}}/status', [
                    'status_id' => 9,
                    'deed_type' => 'electronic',
                    'deed_number' => '1234567890',
                ], 'deed_type: paper | electronic | other'),
                $makePost('Status 10 — Waiting supervisor', 'api/admin/orders/{{id}}/status', [
                    'status_id' => 10,
                    'ejar_contract_number' => 'EJR-2026-001',
                    'notes' => 'ملاحظة اختيارية',
                ], ''),
                $makePost('Status 8 — Send draft to client', 'api/admin/orders/{{id}}/status', [
                    'status_id' => 8,
                    'ejar_contract_draft_number' => 'DRAFT-001',
                    'contact_number_mode' => 'same',
                    'contact_number' => '0500000000',
                ], 'contact_number_mode: same | another (contact_number required if another)'),
                $makePost('Status 2 — Return (optional file via form-data)', 'api/admin/orders/{{id}}/status', [
                    'status_id' => 2,
                ], 'For a file, switch body to form-data: status_id + attachment'),
                $makePost('Accept / reject return', 'api/admin/orders/{{id}}/return-contract-status', [
                    'accept_retrun_contract' => true,
                ], ''),
            ],
        ],
        [
            'name' => '06 Receive contract',
            'item' => [
                $makeGet('Show received row', 'api/admin/received-contracts/{{id}}', [], ''),
                $makePost('Receive order', 'api/admin/received-contracts', [
                    'contract_id' => '{{id}}',
                    'notes' => 'تم الاستلام',
                ], 'Allowed only when current status is جديد (1). Sets status to مستلم (6).'),
            ],
        ],
        [
            'name' => '07 Comments',
            'item' => [
                $makeGet('List comments', 'api/admin/orders/{{id}}/comments', [['key' => 'per_page', 'value' => '20']], ''),
                $makePost('Add comment', 'api/admin/orders/{{id}}/comments', [
                    'comment' => 'تعليق على العقد',
                ], ''),
                $makePost('Update comment', 'api/admin/orders/{{id}}/comments/{{comment_id}}', [
                    'comment' => 'تعديل التعليق',
                ], ''),
                $makePost('Delete comment', 'api/admin/orders/{{id}}/comments/{{comment_id}}/delete', [], ''),
            ],
        ],
        [
            'name' => '08 Units',
            'item' => [
                $makeGet('List units', 'api/admin/orders/{{id}}/units', [], ''),
                $makePost('Sync units', 'api/admin/orders/{{id}}/units/sync', [
                    'unit_ids' => [1, 2],
                ], ''),
            ],
        ],
    ],
];

file_put_contents(
    $out,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n"
);

echo "Wrote {$out}\n";
