<?php

/**
 * Profits & costs (P&L) tab only.
 * Run: php postman/generate_profits_report_collection.php
 */

$out = __DIR__.'/AQDI-Admin-Profits-Report.postman_collection.json';

$authHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
];

$jsonHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
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

    $parts = array_values(array_filter(explode('/', $path), static fn ($p) => $p !== ''));
    $raw = '{{baseUrl}}/'.$path.($enabled !== [] ? ('?'.implode('&', $enabled)) : '');

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

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-profits-report-9e4b2c11',
        'name' => 'AQDI Admin — Profits Report',
        'description' => "تبويب الأرباح والتكاليف فقط — مستوى المشروع كله.\n\n**GET** `{{baseUrl}}/api/admin/reports/profits`\n**Auth:** Bearer `{{employee_token}}` + صلاحية `analytics.view`\nالرواتب تظهر فقط مع `employee_salaries.view`.\n\n1) شغّل **Employee login**.\n2) طلب **الكل** يطابق الشاشة (KPI + قائمة P&L + ربحية الخدمات).\n\n**Query:** period = all | today | yesterday | last_7_days | last_30_days | custom\nمع custom: date_from + date_to (YYYY-MM-DD)\n\n**kpis:** customer_income, gross_profit, net_profit, margin_percent, profit_per_order, ad_spend, ejar_platform_fees, gateway_fee, messaging_cost\n**pnl[]:** دخل العملاء → مسترجعات → صافي الإيراد → إيجار → موياسر → الرسائل → إجمالي الربح → إعلانات → تشغيلية → رواتب → صافي الربح\n**service_profitability[]:** توثيق سكني/تجاري سنة أولى وإضافية (revenue, ejar_fee, gateway_fee, profit, margin_percent)",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
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
            'name' => '02 Profits (P&L)',
            'item' => [
                $makeGet('الكل (كل الفترات)', 'api/admin/reports/profits', [
                    ['key' => 'period', 'value' => 'all'],
                ], 'يطابق شاشة الأرباح والتكاليف لكل المشروع.'),
                $makeGet('هذا الشهر (آخر 30 يوماً)', 'api/admin/reports/profits', [
                    ['key' => 'period', 'value' => 'last_30_days'],
                ], ''),
                $makeGet('آخر 7 أيام', 'api/admin/reports/profits', [
                    ['key' => 'period', 'value' => 'last_7_days'],
                ], ''),
                $makeGet('اليوم', 'api/admin/reports/profits', [
                    ['key' => 'period', 'value' => 'today'],
                ], ''),
                $makeGet('مدة محددة', 'api/admin/reports/profits', [
                    ['key' => 'period', 'value' => 'custom'],
                    ['key' => 'date_from', 'value' => '2026-08-01'],
                    ['key' => 'date_to', 'value' => '2026-08-24'],
                ], 'يجب إرسال date_from و date_to معاً.'),
            ],
        ],
        [
            'name' => '03 Profit settings',
            'item' => [
                $makeGet('عرض إعدادات الأرباح', 'api/admin/reports/profit-settings', [], 'moyasar_mada_percent, moyasar_credit_percent, moyasar_fixed_fee, marketing_budget. الرواتب مع employee_salaries.view.'),
                [
                    'name' => 'تحديث إعدادات الأرباح',
                    'request' => [
                        'method' => 'PUT',
                        'header' => array_merge($authHeaders, [
                            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                        ]),
                        'url' => [
                            'raw' => '{{baseUrl}}/api/admin/reports/profit-settings',
                            'host' => ['{{baseUrl}}'],
                            'path' => ['api', 'admin', 'reports', 'profit-settings'],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'moyasar_mada_percent' => 1.75,
                                'moyasar_credit_percent' => 2.5,
                                'moyasar_fixed_fee' => 1,
                                'moyasar_fee_percent' => 2.5,
                                'marketing_budget' => 6500,
                                'operating_budget' => null,
                                'monthly_salaries' => 13000,
                            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                            'options' => ['raw' => ['language' => 'json']],
                        ],
                        'description' => 'صلاحية analytics.edit. monthly_salaries يحتاج employee_salaries.edit. moyasar_fee_percent مرادف لـ credit percent.',
                    ],
                    'response' => [],
                ],
            ],
        ],
    ],
];

file_put_contents($out, json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
echo "Wrote {$out}\n";
