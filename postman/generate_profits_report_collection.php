<?php

/**
 * Profits & costs (P&L) tab plus the performance tab that shares its settings.
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
        'description' => "تبويب الأرباح والتكاليف فقط — مستوى المشروع كله.\n\n**GET** `{{baseUrl}}/api/admin/reports/profits`\n**Auth:** Bearer `{{employee_token}}` + صلاحية `analytics.view`\nالرواتب تظهر فقط مع `employee_salaries.view`.\n\n1) شغّل **Employee login**.\n2) طلب **الكل** يطابق الشاشة (KPI + قائمة P&L + ربحية الخدمات + اقتصاد الوحدة + ملخص حسب المصدر).\n\n**Query:** period = all | today | yesterday | last_7_days | last_30_days | custom\nمع custom: date_from + date_to (YYYY-MM-DD)\n\n**kpis:** customer_income, gross_profit, net_profit, margin_percent, profit_per_order, ad_spend, ejar_platform_fees, gateway_fee, messaging_cost, paid_contracts_count, operating_profit_per_contract, monthly_break_even_contracts, cac, proration_days\n**pnl[]:** دخل العملاء → مسترجعات → صافي الإيراد → إيجار → موياسر → الرسائل → إجمالي الربح → إعلانات → تشغيلية → رواتب → صافي الربح\nالثابت الشهري (رواتب / تشغيل / إعلانات) يُقسّم على أيام الفترة ÷ 30.\n**unit_economics[]:** توثيق سكني/تجاري سنة أولى وإضافية + نقل العداد (customer_pays, ejar_fee, moyasar_fee percent+fixed, margin, low_margin)\n**source_summary:** سنة أولى سكني/تجاري، سنوات إضافية، نقل عدادات، مسترجعات\n**collected_breakdown:** documentation vs meter_transfers\n\n---\n\nالمجلد **04 Performance** يغطي `GET {{baseUrl}}/api/admin/reports/performance` (تبويب لوحة الأداء) بنفس المصادقة والفترات، ويشترك مع تبويب الأرباح في نفس إعدادات موياسر/الميزانيات.",
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
                $makeGet('عرض إعدادات الأرباح', 'api/admin/reports/profit-settings', [], 'moyasar_mada_percent, moyasar_credit_percent, moyasar_fixed_fee, marketing_budget, operating_budget, meter_transfer_fee. الرواتب مع employee_salaries.view.'),
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
                                'meter_transfer_fee' => 10,
                            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                            'options' => ['raw' => ['language' => 'json']],
                        ],
                        'description' => 'صلاحية analytics.edit. monthly_salaries يحتاج employee_salaries.edit. moyasar_fee_percent مرادف لـ credit percent.',
                    ],
                    'response' => [],
                ],
            ],
        ],
        [
            'name' => '04 Performance (لوحة الأداء)',
            'item' => [
                $makeGet('الكل (كل الفترات)', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'all'],
                ], "تبويب لوحة الأداء كامل في استجابة واحدة.\n\n**kpis:** total_count, documented_count, working_count, canceled_count, refunded_count, revenue\n**conversion_funnel[]:** بداية طلب → طلب مكتمل البيانات → مسودة عقد → مدفوع → موثّق (مع from_previous_pct)\n**conversion_leakage:** count + percent\n**conversion_rates[]:** عدم الإكمال، المسودة إلى دفع، الاستلام، التوثيق، الإلغاء، الاسترجاع\n**daily_orders[]:** date + label + value\n**orders_by_status[]:** الحالات غير الصفرية فقط\n**by_contract_type[] / by_employee[] / by_document_type[]** (نوع الصك)\n**operational_metrics:** waiting_count, avg_wait_seconds, longest_wait_seconds, late_over_15m, late_over_30m, sla_percent (15 دقيقة), unclaim_count\n**revenue_by_payment_method[] / pnl[] / unit_economics[] / financial_summary[]**\n**refund_requests_by_status[] + refund_requests_total**\n\n`correction_errors` تُرجع [] دائماً — لا يوجد كيان لطلبات التصحيح بعد، و `unclaim_count` تُرجع 0 لعدم وجود تراجع عن الاستلام."),
                $makeGet('هذا الشهر (آخر 30 يوماً)', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'last_30_days'],
                ], ''),
                $makeGet('آخر 7 أيام', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'last_7_days'],
                ], 'الفترات القصيرة (7 أيام أو أقل) تُسمّي أعمدة daily_orders بأسماء أيام الأسبوع.'),
                $makeGet('اليوم', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'today'],
                ], ''),
                $makeGet('سكني — موظف واحد', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'last_30_days'],
                    ['key' => 'contract_type', 'value' => 'housing'],
                    ['key' => 'employee_id', 'value' => '1'],
                ], 'contract_type = housing | commercial، و employee_id يقصر كل الأرقام على الموظف.'),
                $makeGet('مدة محددة', 'api/admin/reports/performance', [
                    ['key' => 'period', 'value' => 'custom'],
                    ['key' => 'date_from', 'value' => '2026-08-01'],
                    ['key' => 'date_to', 'value' => '2026-08-25'],
                ], 'يجب إرسال date_from و date_to معاً.'),
            ],
        ],
    ],
];

file_put_contents($out, json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
echo "Wrote {$out}\n";
