<?php

/**
 * Admin Employee KPIs collection.
 * Run: php postman/generate_employee_kpis_collection.php
 */

$out = __DIR__.'/AQDI-Admin-Employee-KPIs.postman_collection.json';

$authHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
];

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

$periodQuery = static fn (string $period): array => [
    ['key' => 'period', 'value' => $period],
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
                '  const id = json?.data?.id;',
                '  if (id) { pm.collectionVariables.set("employee_id", String(id)); }',
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
        'description' => 'Run first. Saves employee_token and employee_id.',
    ],
    'response' => [],
];

$periodHelp = <<<'TXT'
period = today | yesterday | last_7_days | last_30_days | all

List (all employees): cards + avg_receive + receive_sla
Details: same + received_orders table + full activity log

Cards: استلم / منجز بالفترة / مفتوح الآن / متأخر > 24 س
Metrics: متوسط الاستلام (د عمل) | التزام الاستلام ≤5د
TXT;

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-emp-kpis-'.substr(md5('employee-kpis'), 0, 12),
        'name' => 'AQDI Admin — Employee KPIs',
        'description' => "KPIs لكل موظف بنفس تفاصيل شاشة الداشبورد.\n\nAuth: Bearer {{employee_token}}\n\n".$periodHelp,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
        ['key' => 'employee_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => '01 Auth',
            'item' => [$login],
        ],
        [
            'name' => '02 Employee KPIs',
            'item' => [
                $makeGet(
                    'My KPIs (me)',
                    'api/admin/employees/me/kpis',
                    $periodQuery('today'),
                    $periodHelp."\n\nIncludes activity (آخر التحركات)."
                ),
                $makeGet(
                    'All employees KPIs',
                    'api/admin/employees/kpis',
                    $periodQuery('today'),
                    $periodHelp."\n\nList: cards + avg + SLA. No table / activity."
                ),
                $makeGet(
                    'One employee KPIs (details)',
                    'api/admin/employees/{{employee_id}}/kpis/details',
                    $periodQuery('today'),
                    $periodHelp."\n\nIncludes received_orders table + سجل التحركات الكامل."
                ),
                $makeGet(
                    'One employee KPIs',
                    'api/admin/employees/{{employee_id}}/kpis',
                    $periodQuery('today'),
                    'Same payload as /kpis/details.'
                ),
                $makeGet(
                    'Yesterday',
                    'api/admin/employees/{{employee_id}}/kpis',
                    $periodQuery('yesterday'),
                    ''
                ),
                $makeGet(
                    'Last 7 days',
                    'api/admin/employees/{{employee_id}}/kpis',
                    $periodQuery('last_7_days'),
                    ''
                ),
                $makeGet(
                    'Last 30 days',
                    'api/admin/employees/{{employee_id}}/kpis',
                    $periodQuery('last_30_days'),
                    ''
                ),
                $makeGet(
                    'All periods',
                    'api/admin/employees/{{employee_id}}/kpis',
                    $periodQuery('all'),
                    ''
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
