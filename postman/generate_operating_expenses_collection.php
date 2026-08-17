<?php

/**
 * Admin Operating Expenses collection (full CRUD).
 * Run: php postman/generate_operating_expenses_collection.php
 */

$out = __DIR__.'/AQDI-Admin-Operating-Expenses.postman_collection.json';

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

$makeJson = static function (
    string $name,
    string $method,
    string $path,
    array $body,
    string $description,
    array $events = []
) use ($jsonHeaders): array {
    $parts = array_values(array_filter(explode('/', $path), static fn ($p) => $p !== ''));
    $item = [
        'name' => $name,
        'request' => [
            'method' => $method,
            'header' => $jsonHeaders,
            'url' => [
                'raw' => '{{baseUrl}}/'.$path,
                'host' => ['{{baseUrl}}'],
                'path' => $parts,
            ],
            'description' => $description,
        ],
        'response' => [],
    ];

    if ($body !== []) {
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    if ($events !== []) {
        $item['event'] = $events;
    }

    return $item;
};

$saveIdScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'try {',
                '  const json = pm.response.json();',
                '  const id = json?.data?.id;',
                '  if (id) { pm.collectionVariables.set("operating_expense_id", String(id)); }',
                '} catch (e) {}',
            ],
        ],
    ],
];

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
        'description' => 'Run first. Saves employee_token automatically.',
    ],
    'response' => [],
];

$listQuery = [
    ['key' => 'page', 'value' => '1'],
    ['key' => 'per_page', 'value' => '20'],
    ['key' => 'search', 'value' => 'إيجار', 'disabled' => true],
    ['key' => 'created_at', 'value' => 'month', 'disabled' => true],
];

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-opex-'.substr(md5('operating-expenses'), 0, 12),
        'name' => 'AQDI Admin — Operating Expenses',
        'description' => <<<'TXT'
مصاريف التشغيل — Admin API CRUD.

Auth: Bearer {{employee_token}} (employee Sanctum).

1) Run **Employee login** first.
2) Create an expense — `operating_expense_id` is saved automatically.
3) Use list / show / update / delete.

Fields:
- expense (string, required) — اسم المصروف
- amount (number, required) — المبلغ

List query:
- search
- created_at = today | week | month | year
- page, per_page

List response includes `data.summary.count` and `data.summary.total_amount`.
TXT,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
        ['key' => 'operating_expense_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => '01 Auth',
            'item' => [$login],
        ],
        [
            'name' => '02 Operating expenses',
            'item' => [
                $makeGet(
                    'List operating expenses',
                    'api/admin/operating-expenses',
                    $listQuery,
                    'Returns items + pagination + summary (count, total_amount). created_at: today | week | month | year'
                ),
                $makeJson(
                    'Create operating expense',
                    'POST',
                    'api/admin/operating-expenses',
                    ['expense' => 'إيجار المكتب', 'amount' => 4500],
                    'Saves operating_expense_id from the response.',
                    $saveIdScript
                ),
                $makeGet(
                    'Show operating expense',
                    'api/admin/operating-expenses/{{operating_expense_id}}',
                    [],
                    ''
                ),
                $makeJson(
                    'Update operating expense (POST)',
                    'POST',
                    'api/admin/operating-expenses/{{operating_expense_id}}',
                    ['expense' => 'إيجار المكتب', 'amount' => 4800],
                    'Partial update is allowed (send only the fields to change).'
                ),
                $makeJson(
                    'Update operating expense (PUT)',
                    'PUT',
                    'api/admin/operating-expenses/{{operating_expense_id}}',
                    ['expense' => 'إيجار المكتب', 'amount' => 4800],
                    'Same as POST update.'
                ),
                $makeJson(
                    'Delete operating expense (POST)',
                    'POST',
                    'api/admin/operating-expenses/{{operating_expense_id}}/delete',
                    [],
                    'Soft delete.'
                ),
                $makeJson(
                    'Delete operating expense (DELETE)',
                    'DELETE',
                    'api/admin/operating-expenses/{{operating_expense_id}}',
                    [],
                    'Same as POST /delete.'
                ),
            ],
        ],
    ],
];

file_put_contents(
    $out,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n"
);

echo "Wrote {$out}\n";
