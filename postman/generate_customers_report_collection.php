<?php

/**
 * Customers report tab only.
 * Run: php postman/generate_customers_report_collection.php
 */

$out = __DIR__.'/AQDI-Admin-Customers-Report.postman_collection.json';

$authHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
];

$jsonHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
];

$makeGet = static function (string $name, array $query, string $description) use ($authHeaders): array {
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

    $raw = '{{baseUrl}}/api/admin/reports/customers'.($enabled !== [] ? ('?'.implode('&', $enabled)) : '');

    return [
        'name' => $name,
        'request' => [
            'method' => 'GET',
            'header' => $authHeaders,
            'url' => [
                'raw' => $raw,
                'host' => ['{{baseUrl}}'],
                'path' => ['api', 'admin', 'reports', 'customers'],
                'query' => $queryItems,
            ],
            'description' => $description,
        ],
        'response' => [],
    ];
};

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-customers-report-7c2e1a90',
        'name' => 'AQDI Admin — Customers Report',
        'description' => "تبويب العملاء فقط — مستوى المشروع كله (ليس عقداً واحداً).\n\n**GET** `{{baseUrl}}/api/admin/reports/customers`\n**Auth:** Bearer `{{employee_token}}` + صلاحية `analytics.view`\n\n1) شغّل **Employee login** أولاً.\n2) طلب **الكل** يطابق الشاشة (KPI + رسم الجدد/العائدين + أفضل العملاء).\n\n**Query:**\n- period = all | today | yesterday | last_7_days | last_30_days | custom\n- date_from + date_to (مع custom) بصيغة YYYY-MM-DD\n- contract_type = housing | commercial (اختياري)\n- employee_id (اختياري)\n\n**Response data:**\n- kpis.total إجمالي العملاء\n- kpis.new عملاء جدد\n- kpis.returning عملاء عائدون\n- kpis.avg_contracts_per_customer متوسط العقود\n- kpis.incomplete لم يكملوا الطلب\n- segments[] رسم الجدد مقابل العائدين\n- top_customers[] name, mobile, contracts_count, paid_count, total_spending",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
        ['key' => 'employee_id', 'value' => ''],
    ],
    'item' => [
        [
            'name' => '01 Auth',
            'item' => [
                [
                    'name' => 'Employee login',
                    'event' => [
                        [
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
                        ],
                    ],
                    'request' => [
                        'method' => 'POST',
                        'header' => $jsonHeaders,
                        'url' => [
                            'raw' => '{{baseUrl}}/api/admin/employees/login',
                            'host' => ['{{baseUrl}}'],
                            'path' => ['api', 'admin', 'employees', 'login'],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => "{\n    \"email\": \"mohammed@aqdi.com\",\n    \"password\": \"password\",\n    \"fcm_token\": \"{{fcm_token}}\"\n}",
                            'options' => ['raw' => ['language' => 'json']],
                        ],
                        'description' => 'يحفظ employee_token تلقائياً.',
                    ],
                    'response' => [],
                ],
            ],
        ],
        [
            'name' => '02 Customers report',
            'item' => [
                $makeGet('الكل (كل الفترات)', [
                    ['key' => 'period', 'value' => 'all'],
                    ['key' => 'contract_type', 'value' => 'housing', 'disabled' => true],
                    ['key' => 'employee_id', 'value' => '{{employee_id}}', 'disabled' => true],
                ], 'الشاشة الرئيسية: كل الفترات / كل الأنواع / كل الموظفين.'),
                $makeGet('هذا الشهر (آخر 30 يوماً)', [
                    ['key' => 'period', 'value' => 'last_30_days'],
                ], 'فلتر آخر 30 يوماً.'),
                $makeGet('آخر 7 أيام', [
                    ['key' => 'period', 'value' => 'last_7_days'],
                ], ''),
                $makeGet('اليوم', [
                    ['key' => 'period', 'value' => 'today'],
                ], ''),
                $makeGet('مدة محددة', [
                    ['key' => 'period', 'value' => 'custom'],
                    ['key' => 'date_from', 'value' => '2026-08-01'],
                    ['key' => 'date_to', 'value' => '2026-08-24'],
                ], 'يجب إرسال date_from و date_to معاً.'),
                $makeGet('سكني فقط', [
                    ['key' => 'period', 'value' => 'all'],
                    ['key' => 'contract_type', 'value' => 'housing'],
                ], 'contract_type=housing أو commercial.'),
            ],
        ],
    ],
];

file_put_contents($out, json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
echo "Wrote {$out}\n";
