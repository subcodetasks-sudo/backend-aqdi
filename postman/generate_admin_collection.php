<?php

/**
 * Generate full Postman collection for all /api/admin routes.
 * Run: php postman/generate_admin_collection.php
 */

$root = dirname(__DIR__);
chdir($root);

$routesJson = shell_exec('php artisan route:list --path=api/admin --json 2>NUL');
if (! $routesJson) {
    fwrite(STDERR, "Failed to list routes\n");
    exit(1);
}

$routes = json_decode($routesJson, true);
if (! is_array($routes)) {
    fwrite(STDERR, "Invalid routes JSON\n");
    exit(1);
}

/** @var array<string, mixed> $exampleBodies */
$exampleBodies = [
    'api/admin/employees/login' => [
        'email' => 'mohammed@aqdi.com',
        'password' => 'password',
        'fcm_token' => '{{fcm_token}}',
    ],
    'api/admin/employees' => [
        'POST' => [
            'name' => 'موظف جديد',
            'email' => 'employee@aqdi.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'mobile' => '0500000000',
            'is_active' => true,
        ],
    ],
    'api/admin/employees/{id}' => [
        'POST' => [
            'name' => 'تحديث موظف',
            'email' => 'employee@aqdi.com',
            'mobile' => '0500000001',
            'is_active' => true,
        ],
    ],
    'api/admin/employees/fcm' => [
        'fcm_token' => '{{fcm_token}}',
    ],
    'api/admin/employees/{id}/salary' => [
        'amount' => 5000,
        'note' => 'راتب شهر',
        'date' => '2026-07-01',
    ],
    'api/admin/employees/{id}/note' => [
        'note' => 'ملاحظة على الموظف',
    ],
    'api/admin/notifications/send' => [
        'title' => 'عنوان',
        'body' => 'نص الإشعار',
        'audience' => 'all_users',
    ],
    'api/admin/notifications/user' => [
        'user_id' => '{{user_id}}',
        'title' => 'عنوان',
        'body' => 'نص الإشعار',
    ],
    'api/admin/notifications/custom-user' => [
        'user_id' => '{{user_id}}',
        'title' => 'عنوان',
        'body' => 'نص الإشعار',
    ],
    'api/admin/notifications/employee' => [
        'employee_id' => '{{employee_id}}',
        'title' => 'عنوان',
        'body' => 'نص الإشعار',
    ],
    'api/admin/notifications/custom-employee' => [
        'employee_id' => '{{employee_id}}',
        'title' => 'عنوان',
        'body' => 'نص الإشعار',
    ],
    'api/admin/notifications/all-users' => [
        'title' => 'عنوان',
        'body' => 'نص لجميع المستخدمين',
    ],
    'api/admin/notifications/all-employees' => [
        'title' => 'عنوان',
        'body' => 'نص لجميع الموظفين',
    ],
    'api/admin/orders/{id}' => [
        'POST' => [
            'notes_edits' => 'تعديل ملاحظات',
            'contract_status_id' => '{{status_id}}',
        ],
    ],
    'api/admin/orders/{id}/contract-status' => [
        'contract_status_id' => '{{status_id}}',
        'deed_type' => 'electronic',
        'deed_number' => '1234567890',
        'ejar_contract_number' => 'EJR-2026-001',
        'notes' => 'ملاحظة اختيارية',
        'ejar_contract_draft_number' => 'DRAFT-001',
        'contact_number_mode' => 'same',
        'contact_number' => '0500000000',
    ],
    'api/admin/orders/{id}/draft-contract-status' => [
        'draft_contract_status_id' => '{{draft_status_id}}',
        'deed_type' => 'electronic',
        'deed_number' => '1234567890',
        'ejar_contract_number' => 'EJR-2026-001',
        'notes' => 'ملاحظة اختيارية',
        'ejar_contract_draft_number' => 'DRAFT-001',
        'contact_number_mode' => 'same',
        'contact_number' => '0500000000',
    ],
    'api/admin/orders/{id}/return-contract-status' => [
        'accept_retrun_contract' => true,
    ],
    'api/admin/orders/{contractId}/comments' => [
        'POST' => [
            'comment' => 'تعليق على العقد',
        ],
    ],
    'api/admin/orders/{contractId}/comments/{commentId}' => [
        'POST' => [
            'comment' => 'تعديل التعليق',
        ],
    ],
    'api/admin/orders/{contractId}/units' => [
        'POST' => [
            'unit_ids' => [1, 2],
        ],
    ],
    'api/admin/orders/{contractId}/units/sync' => [
        'unit_ids' => [1, 2, 3],
    ],
    'api/admin/received-contracts' => [
        'contract_id' => '{{contract_id}}',
        'notes' => 'تم الاستلام',
        'date_of_received' => '2026-07-23',
    ],
    'api/admin/received-contracts/{contractId}' => [
        'PATCH' => [
            'status' => 'مستلم',
            'notes' => 'تحديث الاستلام',
        ],
    ],
    'api/admin/refundable-contracts' => [
        'POST' => [
            'contract_id' => '{{contract_id}}',
            'refund_amount' => 349,
            'notes' => 'استرجاع',
            'has_draft_contract' => false,
        ],
    ],
    'api/admin/analytics/refunds/contracts/confirm' => [
        'uuid' => '{{contract_uuid}}',
        'is_refunded' => true,
    ],
    'api/admin/contract-paid-by-employees' => [
        'POST' => [
            'contract_uuid' => '{{contract_uuid}}',
            'amount' => 349,
        ],
    ],
    'api/admin/finance/expenses' => [
        'POST' => [
            'title' => 'مصروف',
            'amount' => 100,
            'note' => 'ملاحظة',
            'date' => '2026-07-23',
        ],
    ],
    'api/admin/finance/expenses/{expense}' => [
        'PUT' => [
            'title' => 'تعديل مصروف',
            'amount' => 150,
            'note' => 'ملاحظة',
            'date' => '2026-07-23',
        ],
    ],
    'api/admin/operating-expenses' => [
        'POST' => [
            'expense' => 'إيجار المكتب',
            'amount' => 4500,
        ],
    ],
    'api/admin/operating-expenses/{id}' => [
        'POST' => [
            'expense' => 'إيجار المكتب',
            'amount' => 4800,
        ],
        'PUT' => [
            'expense' => 'إيجار المكتب',
            'amount' => 4800,
        ],
    ],
    'api/admin/contract-statuses' => [
        'POST' => [
            'name' => 'حالة جديدة',
            'color' => '#3B82F6',
            'color_text' => '#FFFFFF',
            'description' => 'وصف داخلي للأدمن',
            'client_explanation' => 'شرح الحالة الذي يظهر للعميل في تتبع الطلب',
            'order' => 10,
            'is_active' => true,
        ],
    ],
    'api/admin/contract-statuses/{id}' => [
        'POST' => [
            'name' => 'تعديل حالة',
            'color' => '#F59E0B',
            'color_text' => '#000000',
            'description' => 'وصف',
            'client_explanation' => 'شرح محدث يظهر للعميل',
            'is_active' => true,
        ],
    ],
    'api/admin/draft-contract-statuses' => [
        'POST' => [
            'name' => 'حالة مسودة',
            'color' => '#8B5CF6',
            'color_text' => '#FFFFFF',
            'description' => 'وصف',
            'client_explanation' => 'شرح الحالة للعميل في المسودة',
            'is_active' => true,
        ],
    ],
    'api/admin/draft-contract-statuses/{id}' => [
        'POST' => [
            'name' => 'تعديل حالة مسودة',
            'color' => '#8B5CF6',
            'client_explanation' => 'شرح محدث للعميل',
            'is_active' => true,
        ],
    ],
    'api/admin/tenant-roles' => [
        'POST' => [
            'name' => 'صفة مستأجر',
            'service_definition' => 'تعريف الخدمة',
            'input_field_label' => 'القيمة',
            'input_field_type' => 'text',
            'icon' => null,
            'input_icon' => null,
            'pop' => true,
            'is_active' => true,
        ],
    ],
    'api/admin/tenant-roles/{id}' => [
        'POST' => [
            'name' => 'تعديل صفة',
            'service_definition' => 'تعريف',
            'input_field_label' => 'القيمة',
            'input_field_type' => 'number',
            'pop' => false,
            'is_active' => true,
        ],
    ],
    'api/admin/regions' => [
        'POST' => [
            'name_ar' => 'منطقة',
            'name_en' => 'Region',
            'is_active' => true,
        ],
    ],
    'api/admin/cities' => [
        'POST' => [
            'name_ar' => 'مدينة',
            'name_en' => 'City',
            'region_id' => 1,
            'is_active' => true,
        ],
    ],
    'api/admin/coupons' => [
        'POST' => [
            'code_coupon' => 'AQDI10',
            'type_coupon' => 'ratio',
            'value_coupon' => 10,
            'date_start' => '2026-01-01',
            'date_end' => '2026-12-31',
            'usage' => 100,
            'usage_of_user' => 1,
        ],
    ],
    'api/admin/contract-whatsapp/complete' => [
        'mobile_number' => '0500000000',
        'addition_date' => '2026-07-23',
        'contract_type' => 'commercial',
        'is_documented' => false,
        'amount_paid_by_client' => 349,
        'rental_fees' => 0,
        'notes' => 'عقد واتساب مكتمل',
    ],
    'api/admin/contract-whatsapp/incomplete' => [
        'mobile_number' => '0500000000',
        'notes' => 'غير مكتمل',
        'time' => '11:00',
        'date' => '2026-07-23',
    ],
    'api/admin/sms/message' => [
        'mobile' => '0500000000',
        'message' => 'رسالة تجريبية',
    ],
    'api/admin/sms/send' => [
        'mobile' => '0500000000',
        'message' => 'رسالة تجريبية',
    ],
    'api/admin/sms-settings' => [
        'POST' => [
            'messages' => [
                'welcome' => 'مرحباً بك',
            ],
        ],
    ],
    'api/admin/meter-fee-settings' => [
        'POST' => [
            'housing_electricity_meter_fee' => 0,
            'housing_water_meter_fee' => 0,
            'commercial_electricity_meter_fee' => 0,
            'commercial_water_meter_fee' => 0,
        ],
    ],
    'api/admin/settings' => [
        'POST' => [
            'application_fees' => 0,
            'housing_tax' => 0,
            'commercial_tax' => 0,
        ],
    ],
    'api/admin/popup-contracts' => [
        'POST' => [
            'title' => 'عنوان',
            'body' => 'محتوى',
            'is_active' => true,
        ],
    ],
    'api/admin/payment-messages' => [
        'POST' => [
            'type' => 'success',
            'title' => 'نجاح الدفع',
            'body' => 'تم الدفع بنجاح',
            'is_active' => true,
        ],
    ],
    'api/admin/faqs' => [
        'POST' => [
            'question' => 'سؤال؟',
            'answer' => 'إجابة',
            'is_active' => true,
        ],
    ],
    'api/admin/ads' => [
        'POST' => [
            'title' => 'إعلان',
            'is_active' => true,
        ],
    ],
    'api/admin/roles' => [
        'POST' => [
            'name' => 'role_name',
            'display_name' => 'دور',
            'permissions' => [1, 2],
        ],
    ],
    'api/admin/roles/{id}/assign-permissions' => [
        'permissions' => [1, 2, 3],
    ],
    'api/admin/permissions' => [
        'POST' => [
            'name' => 'permission_name',
            'display_name' => 'صلاحية',
            'section' => 'orders',
        ],
    ],
    'api/admin/content-pages/{pageKey}' => [
        'POST' => [
            'title' => 'عنوان الصفحة',
            'body' => 'محتوى الصفحة',
        ],
    ],
    'api/admin/content/terms-and-conditions' => [
        'POST' => [
            'content' => 'نص الشروط',
        ],
    ],
    'api/admin/content/privacy' => [
        'POST' => [
            'content' => 'نص الخصوصية',
        ],
    ],
];

$queryExamples = [
    'api/admin/orders' => [
        ['key' => 'status_id', 'value' => '{{status_id}}'],
        ['key' => 'status', 'value' => '{{status_id}}', 'disabled' => true],
        ['key' => 'contract_status_id', 'value' => '{{status_id}}', 'disabled' => true],
        ['key' => 'page', 'value' => '1'],
        ['key' => 'per_page', 'value' => '20'],
        ['key' => 'complete', 'value' => '1', 'disabled' => true],
        ['key' => 'incomplete', 'value' => '1', 'disabled' => true],
        ['key' => 'is_completed', 'value' => '1', 'disabled' => true],
        ['key' => 'search', 'value' => '', 'disabled' => true],
    ],
    'api/admin/orders/return' => [
        ['key' => 'page', 'value' => '1'],
        ['key' => 'per_page', 'value' => '20'],
    ],
    'api/admin/orders/incomplete/list' => [
        ['key' => 'page', 'value' => '1'],
    ],
    'api/admin/contract-whatsapp' => [
        ['key' => 'is_complete', 'value' => '1', 'disabled' => true],
        ['key' => 'mobile_number', 'value' => '050', 'disabled' => true],
        ['key' => 'per_page', 'value' => '20'],
    ],
    'api/admin/users' => [
        ['key' => 'page', 'value' => '1'],
        ['key' => 'search', 'value' => '', 'disabled' => true],
    ],
    'api/admin/payments' => [
        ['key' => 'page', 'value' => '1'],
    ],
    'api/admin/operating-expenses' => [
        ['key' => 'page', 'value' => '1'],
        ['key' => 'per_page', 'value' => '20'],
        ['key' => 'search', 'value' => '', 'disabled' => true],
        ['key' => 'created_at', 'value' => 'month', 'disabled' => true],
    ],
    'api/admin/refundable-contracts' => [
        ['key' => 'page', 'value' => '1'],
    ],
];

function statusCaseDescription(string $uri): string
{
    if (! str_contains($uri, '/contract-status') && ! str_contains($uri, '/draft-contract-status')) {
        return '';
    }

    if (str_contains($uri, '/return-contract-status')) {
        return '';
    }

    return <<<'TXT'


Status extra fields (required only for the matching status):

- Status ID 9 (توثيق العقد في إيجار):
  deed_type = paper | electronic | other
  deed_number (required)

- Status ID 10 (بانتظار المشرف):
  ejar_contract_number (required)
  notes (optional)

- Status ID 2 (استرجاع / مسترجع):
  attachment (optional file — send as multipart form-data)

- Send contract draft to client (ID 8 / إرسال مسودة العقد لكم عبر واتساب):
  ejar_contract_draft_number (required)
  contact_number_mode = same | another
  contact_number (required when contact_number_mode = another)
TXT;
}

function folderNameFromUri(string $uri): string
{
    $parts = explode('/', $uri);
    // api / admin / {segment}
    $segment = $parts[2] ?? 'general';

    $map = [
        'employees' => '01 Auth & Employees',
        'notifications' => '02 Notifications (Firebase)',
        'analytics' => '03 Analytics & Dashboard',
        'dashboard-analytics' => '03 Analytics & Dashboard',
        'orders' => '04 Orders & Contracts',
        'contracts' => '04 Orders & Contracts',
        'received-contracts' => '04 Orders & Contracts',
        'refundable-contracts' => '05 Refunds',
        'payments' => '06 Payments & Finance',
        'payment-gateway' => '06 Payments & Finance',
        'finance' => '06 Payments & Finance',
        'operating-expenses' => '06 Payments & Finance',
        'contract-paid-by-employees' => '06 Payments & Finance',
        'users' => '07 Users',
        'regions' => '08 Locations',
        'cities' => '08 Locations',
        'real-estates' => '09 Real Estate & Units',
        'real-estate-types' => '09 Real Estate & Units',
        'real-estate-usages' => '09 Real Estate & Units',
        'unit-real-estates' => '09 Real Estate & Units',
        'unit-types' => '09 Real Estate & Units',
        'unit-usages' => '09 Real Estate & Units',
        'tenant-roles' => '10 Tenant Roles',
        'roles' => '11 Roles & Permissions',
        'permissions' => '11 Roles & Permissions',
        'contract-statuses' => '12 Contract Statuses',
        'draft-contract-statuses' => '12 Contract Statuses',
        'contract-periods' => '13 Contract Periods',
        'contract-whatsapp' => '14 WhatsApp Contracts',
        'coupons' => '15 Coupons',
        'paperworks' => '16 Content & CMS',
        'popup-contracts' => '16 Content & CMS',
        'payment-messages' => '16 Content & CMS',
        'setting-contracts' => '16 Content & CMS',
        'instrument-type-settings' => '16 Content & CMS',
        'faqs' => '16 Content & CMS',
        'instruction-sections' => '16 Content & CMS',
        'message-alert-sections' => '17 Message Alerts',
        'message-alert-section-items' => '17 Message Alerts',
        'message-alerts' => '17 Message Alerts',
        'blogs' => '18 Blogs',
        'sms' => '19 SMS',
        'sms-settings' => '19 SMS',
        'meter-fee-settings' => '20 Settings',
        'settings' => '20 Settings',
        'app-content' => '20 Settings',
        'payment-types' => '20 Settings',
        'customer-messages' => '20 Settings',
        'content' => '16 Content & CMS',
        'content-pages' => '16 Content & CMS',
        'ads' => '21 Ads',
    ];

    return $map[$segment] ?? ('99 '.ucfirst(str_replace('-', ' ', $segment)));
}

function normalizeUriTemplate(string $uri): string
{
    // Keep laravel-like placeholders for matching examples
    return preg_replace('#\{[^}]+\}#', '{id}', $uri) ?? $uri;
}

function resolveBody(array $exampleBodies, string $uri, string $method): array|null
{
    $method = strtoupper($method);

    // Exact uri match
    if (isset($exampleBodies[$uri])) {
        $v = $exampleBodies[$uri];
        if (isset($v[$method]) && is_array($v[$method])) {
            return $v[$method];
        }
        if (! isset($v['GET']) && ! isset($v['POST']) && ! isset($v['PUT']) && ! isset($v['PATCH']) && ! isset($v['DELETE'])) {
            return is_array($v) ? $v : null;
        }
    }

    // Match by replacing concrete param names with patterns used in keys
    foreach ($exampleBodies as $pattern => $body) {
        $regex = '#^'.preg_replace('#\{[^}]+\}#', '[^/]+', $pattern).'$#';
        if (! preg_match($regex, $uri)) {
            continue;
        }
        if (isset($body[$method]) && is_array($body[$method])) {
            return $body[$method];
        }
        if (! isset($body['GET']) && ! isset($body['POST']) && ! isset($body['PUT']) && ! isset($body['PATCH']) && ! isset($body['DELETE'])) {
            return is_array($body) ? $body : null;
        }
    }

    // Generic fallbacks
    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        if (str_ends_with($uri, '/delete') || str_contains($uri, '/toggle') || str_contains($uri, '/block') || str_contains($uri, '/unblock') || str_contains($uri, '/activate') || str_contains($uri, '/inactive') || str_contains($uri, '/sync') || str_contains($uri, '/logout')) {
            return [];
        }

        return ['example' => true];
    }

    return null;
}

function makeRequestItem(array $route, array $exampleBodies, array $queryExamples): array
{
    $methods = preg_split('/\|/', (string) ($route['method'] ?? 'GET')) ?: ['GET'];
    $methods = array_values(array_filter($methods, fn ($m) => ! in_array(strtoupper(trim($m)), ['HEAD', 'OPTIONS'], true)));
    $uri = (string) ($route['uri'] ?? '');
    $name = (string) ($route['name'] ?? '');
    $action = (string) ($route['action'] ?? '');

    $items = [];
    foreach ($methods as $methodRaw) {
        $method = strtoupper(trim($methodRaw));
        if ($method === '') {
            continue;
        }

        $pathParts = array_values(array_filter(explode('/', $uri), fn ($p) => $p !== ''));
        $rawUrl = '{{baseUrl}}/'.$uri;
        $query = $queryExamples[$uri] ?? [];

        // Replace route params with collection variables where obvious
        $rawUrl = str_replace(
            ['{id}', '{contractId}', '{commentId}', '{unitId}', '{uuid}', '{expense}', '{statusId}', '{sectionId}', '{imageId}', '{pageKey}', '{audience}'],
            ['{{id}}', '{{contract_id}}', '{{comment_id}}', '{{unit_id}}', '{{contract_uuid}}', '{{expense_id}}', '{{status_id}}', '{{section_id}}', '{{image_id}}', 'home', 'client'],
            $rawUrl
        );
        $pathParts = array_map(function ($p) {
            return match ($p) {
                '{id}' => '{{id}}',
                '{contractId}' => '{{contract_id}}',
                '{commentId}' => '{{comment_id}}',
                '{unitId}' => '{{unit_id}}',
                '{uuid}' => '{{contract_uuid}}',
                '{expense}' => '{{expense_id}}',
                '{statusId}' => '{{status_id}}',
                '{sectionId}' => '{{section_id}}',
                '{imageId}' => '{{image_id}}',
                '{pageKey}' => 'home',
                '{audience}' => 'client',
                default => $p,
            };
        }, $pathParts);

        $displayName = $method.' /'.implode('/', array_slice($pathParts, 2));
        if ($name !== '') {
            $displayName .= '  ('.$name.')';
        }

        $headers = [
            ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
            ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
        ];

        $request = [
            'method' => $method,
            'header' => $headers,
            'url' => [
                'raw' => $rawUrl.($query ? ('?'.implode('&', array_map(function ($q) {
                    return $q['key'].'='.$q['value'];
                }, array_filter($query, fn ($q) => empty($q['disabled']))))) : ''),
                'host' => ['{{baseUrl}}'],
                'path' => $pathParts,
            ],
            'description' => trim(
                ($action ? "Action: {$action}\n" : '')
                .($name ? "Route name: {$name}" : '')
                .statusCaseDescription($uri)
            ),
        ];

        if ($query !== []) {
            $request['url']['query'] = array_map(function ($q) {
                $out = [
                    'key' => $q['key'],
                    'value' => $q['value'],
                ];
                if (! empty($q['disabled'])) {
                    $out['disabled'] = true;
                }

                return $out;
            }, $query);
        }

        $body = resolveBody($exampleBodies, $uri, $method);
        if ($body !== null) {
            $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
            $request['header'] = $headers;
            $request['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        $item = [
            'name' => $displayName,
            'request' => $request,
            'response' => [],
        ];

        // Auto-save token on login
        if ($uri === 'api/admin/employees/login' && $method === 'POST') {
            $item['event'] = [[
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
            ]];
        }

        $items[] = $item;
    }

    return $items;
}

$folders = [];
$seen = [];

foreach ($routes as $route) {
    $uri = (string) ($route['uri'] ?? '');
    if ($uri === '' || ! str_starts_with($uri, 'api/admin')) {
        continue;
    }

    $method = (string) ($route['method'] ?? '');
    $key = $method.' '.$uri;
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;

    $folder = folderNameFromUri($uri);
    if (! isset($folders[$folder])) {
        $folders[$folder] = [];
    }

    foreach (makeRequestItem($route, $exampleBodies, $queryExamples) as $item) {
        $folders[$folder][] = $item;
    }
}

ksort($folders);

$collectionItems = [];
foreach ($folders as $folderName => $items) {
    // de-dupe by name inside folder
    $unique = [];
    foreach ($items as $item) {
        $unique[$item['name']] = $item;
    }
    $collectionItems[] = [
        'name' => $folderName,
        'item' => array_values($unique),
    ];
}

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-full-'.substr(md5((string) time()), 0, 12),
        'name' => 'AQDI Admin API — Full Collection',
        'description' => "Full Admin API collection generated from Laravel routes (`api/admin/*`).\n\nAuth: most endpoints need `Authorization: Bearer {{employee_token}}` (employee Sanctum).\n\n1) Run **Employees login** first — token is saved automatically.\n2) Set `baseUrl`, ids, and other variables as needed.\n3) Example JSON bodies are included for create/update endpoints.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
        ['key' => 'contract_id', 'value' => '33'],
        ['key' => 'contract_uuid', 'value' => ''],
        ['key' => 'user_id', 'value' => '1'],
        ['key' => 'employee_id', 'value' => '1'],
        ['key' => 'status_id', 'value' => '6'],
        ['key' => 'draft_status_id', 'value' => '1'],
        ['key' => 'id', 'value' => '1'],
        ['key' => 'comment_id', 'value' => '1'],
        ['key' => 'unit_id', 'value' => '1'],
        ['key' => 'expense_id', 'value' => '1'],
        ['key' => 'operating_expense_id', 'value' => '1'],
        ['key' => 'section_id', 'value' => '1'],
        ['key' => 'image_id', 'value' => '1'],
    ],
    'item' => $collectionItems,
];

$out = $root.'/postman/AQDI-Admin-API-Full.postman_collection.json';
file_put_contents($out, json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$countRequests = 0;
foreach ($collectionItems as $folder) {
    $countRequests += count($folder['item']);
}

echo "Wrote {$out}\n";
echo 'Folders: '.count($collectionItems).PHP_EOL;
echo "Requests: {$countRequests}".PHP_EOL;
