<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-API.postman_collection.json
 * and postman/AQDI-Admin-API.postman_environment.json
 *
 * Run: php tools/generate_admin_postman_collection.php
 */

$basePath = dirname(__DIR__);

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function hdr(bool $bearer, bool $json = false): array
{
    $h = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];
    if ($bearer) {
        $h[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    return $h;
}

/**
 * Postman v2.1 requires url as object (raw + host + path), not a string.
 *
 * @param  array<string, scalar|null>  $query
 * @return array<string, mixed>
 */
function buildUrl(string $adminPath, array $query = []): array
{
    $adminPath = '/'.trim($adminPath, '/');
    $segments = array_values(array_filter(explode('/', $adminPath)));

    $path = array_merge(['api', 'admin'], $segments);
    $queryItems = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $queryItems[] = [
            'key' => (string) $key,
            'value' => (string) $value,
        ];
    }

    $raw = '{{baseUrl}}/'.implode('/', $path);
    if ($queryItems !== []) {
        $raw .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }

    $url = [
        'raw' => $raw,
        'host' => ['{{baseUrl}}'],
        'path' => $path,
    ];

    if ($queryItems !== []) {
        $url['query'] = $queryItems;
    }

    return $url;
}

function req(string $name, string $method, string $path, array $opts = []): array
{
    $bearer = $opts['bearer'] ?? false;
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);

    $request = [
        'method' => $method,
        'header' => hdr($bearer, $body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)),
        'url' => buildUrl($path, $query),
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if (! empty($opts['description'])) {
        $item['request']['description'] = $opts['description'];
    }

    if (! empty($opts['save_token'])) {
        $item['event'] = [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => [
                        'const json = pm.response.json();',
                        'const token = json?.data?.token ?? json?.token;',
                        'if (token) {',
                        '  pm.collectionVariables.set("employee_token", token);',
                        '  pm.environment.set("employee_token", token);',
                        '}',
                    ],
                    'type' => 'text/javascript',
                ],
            ],
        ];
    }

    return $item;
}

$folders = [
    '00 — Auth & setup' => [
        req('Employee login (save token)', 'POST', '/employees/login', [
            'body' => [
                'email' => 'admin@example.com',
                'password' => 'password',
            ],
            'description' => 'Auto-saves token to employee_token on success.',
            'save_token' => true,
        ]),
        req('App content overview', 'GET', '/app-content/overview'),
    ],
    'Employees' => [
        req('List employees', 'GET', '/employees', ['bearer' => true, 'query' => ['per_page' => 20]]),
        req('Employee salary list', 'GET', '/employees/employee-salary', ['bearer' => true]),
        req('Employee notes list', 'GET', '/employees/employee-notes', ['bearer' => true]),
        req('Create employee', 'POST', '/employees', [
            'bearer' => true,
            'body' => [
                'name' => 'موظف تجريبي',
                'email' => 'employee@example.com',
                'password' => 'password123',
                'phone' => '0500000000',
                'role_id' => 1,
                'is_active' => true,
            ],
        ]),
        req('Show employee', 'GET', '/employees/{{employee_id}}', ['bearer' => true]),
        req('Update employee', 'POST', '/employees/{{employee_id}}', [
            'bearer' => true,
            'body' => ['name' => 'موظف محدّث', 'is_active' => true],
        ]),
        req('Store salary', 'POST', '/employees/{{employee_id}}/salary', [
            'bearer' => true,
            'body' => [
                'basic_salary' => 5000,
                'deduction' => 0,
                'bonus' => 500,
                'total' => 5500,
                'notes' => 'راتب شهر مايو',
            ],
        ]),
        req('Store note', 'POST', '/employees/{{employee_id}}/note', [
            'bearer' => true,
            'body' => ['note' => 'ملاحظة على الموظف', 'addition_date' => '2026-05-23'],
        ]),
        req('Toggle status', 'POST', '/employees/{{employee_id}}/toggle-status', ['bearer' => true, 'body' => []]),
        req('Block employee', 'POST', '/employees/{{employee_id}}/block', ['bearer' => true]),
        req('Unblock employee', 'POST', '/employees/{{employee_id}}/unblock', ['bearer' => true]),
        req('Delete employee', 'POST', '/employees/{{employee_id}}/delete', ['bearer' => true]),
        req('Logout', 'POST', '/employees/logout', ['bearer' => true]),
    ],
    'Analytics — dashboard' => [
        req('Analytics', 'GET', '/analytics'),
        req('Analytics all', 'GET', '/analytics/all'),
        req('Dashboard analytics', 'GET', '/dashboard-analytics'),
        req('Location analytics — cities', 'GET', '/analytics/locations/cities'),
        req('Location analytics', 'GET', '/analytics/locations'),
        req('User activity rate', 'GET', '/analytics/user-activity-rate'),
    ],
    'Analytics — customers' => [
        req('Top customers — completed orders', 'GET', '/analytics/top-customers/completed-orders'),
        req('Top customers — incomplete orders', 'GET', '/analytics/top-customers/incomplete-orders'),
        req('Top customers — orders', 'GET', '/analytics/top-customers/orders'),
        req('Top customers — returns', 'GET', '/analytics/top-customers/returns'),
        req('Top customers — real estates', 'GET', '/analytics/top-customers/real-estates'),
        req('Top customers — units', 'GET', '/analytics/top-customers/units'),
        req('Clients — completed orders', 'GET', '/analytics/clients/completed-orders'),
        req('Clients — orders', 'GET', '/analytics/clients/orders'),
    ],
    'Analytics — employees & refunds' => [
        req('Most received orders', 'GET', '/analytics/employees/most-received-orders'),
        req('Most returns', 'GET', '/analytics/employees/most-returns'),
        req('Most documented orders', 'GET', '/analytics/employees/most-documented-orders'),
        req('Employees count', 'GET', '/analytics/employees/count'),
        req('Most unpaid orders', 'GET', '/analytics/employees/most-unpaid-orders'),
        req('Refund contracts list', 'GET', '/analytics/refunds/contracts', ['query' => ['period' => 'month', 'per_page' => 20]]),
        req('Refund contract show', 'GET', '/analytics/refunds/contracts/{{id}}'),
    ],
    'Orders' => [
        req('List orders', 'GET', '/orders', ['query' => ['per_page' => 20, 'page' => 1]]),
        req('List orders — is_received', 'GET', '/orders', ['query' => ['is_received' => 1, 'per_page' => 20]]),
        req('Return orders', 'GET', '/orders/return', ['query' => ['per_page' => 20]]),
        req('Incomplete orders', 'GET', '/orders/incomplete/list', ['query' => ['per_page' => 20]]),
        req('Complete orders', 'GET', '/orders/complete/list', ['query' => ['per_page' => 20]]),
        req('Filter orders by date', 'GET', '/orders/filter', ['query' => ['created_at' => 'month', 'per_page' => 20]]),
        req('Show order (contract detail)', 'GET', '/orders/{{contract_id}}'),
        req('Update contract (partial) — JSON', 'POST', '/orders/{{contract_id}}', [
            'body' => [
                'annual_rent_amount_for_the_unit' => '334',
                'notes_edits' => 'تعديل من الأدمن',
            ],
            'description' => 'Send only fields to change. All contract columns optional except id, uuid, timestamps.',
        ]),
        req('Update contract status', 'POST', '/orders/{{contract_id}}/contract-status', [
            'body' => ['contract_status_id' => 1],
        ]),
        req('Return contract status (accept / reject)', 'POST', '/orders/{{contract_id}}/return-contract-status', [
            'bearer' => true,
            'body' => ['accept_retrun_contract' => true],
            'description' => 'Return order only (contract_status_id = 2). JSON: accept_retrun_contract true = accept, false = reject.',
        ]),
    ],
    'Orders — comments (Bearer)' => [
        req('List comments', 'GET', '/orders/{{contract_id}}/comments', ['bearer' => true, 'query' => ['per_page' => 20]]),
        req('Create comment', 'POST', '/orders/{{contract_id}}/comments', [
            'bearer' => true,
            'body' => ['body' => 'تعليق من الموظف على الطلب'],
        ]),
        req('Update comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}', [
            'bearer' => true,
            'body' => ['body' => 'تعليق محدّث'],
        ]),
        req('Delete comment', 'POST', '/orders/{{contract_id}}/comments/{{comment_id}}/delete', ['bearer' => true]),
    ],
    'Received contracts (Bearer)' => [
        req('Register receipt', 'POST', '/received-contracts', [
            'bearer' => true,
            'body' => [
                'contract_id' => '{{contract_id}}',
                'status' => 'pending',
                'date_of_received' => '2026-05-23',
                'notes' => 'تم الاستلام',
            ],
        ]),
        req('Show receipt', 'GET', '/received-contracts/{{contract_id}}', ['bearer' => true]),
        req('Update receipt — finish', 'PATCH', '/received-contracts/{{contract_id}}', [
            'bearer' => true,
            'body' => ['status' => 'finish', 'date_of_received' => '2026-05-23'],
        ]),
    ],
    'Users' => [
        req('All users', 'GET', '/users', ['query' => ['per_page' => 20, 'page' => 1]]),
        req('Show user (with contracts)', 'GET', '/users/{{user_id}}'),
        req('New users today', 'GET', '/users/new', ['query' => ['per_page' => 20]]),
        req('Users with completed contracts', 'GET', '/users/contracts-complete'),
        req('Toggle user block / unblock', 'POST', '/users/{{user_id}}/block', [
            'description' => 'If user is active → blocks. If blocked → unblocks. Response includes is_active.',
        ]),
        req('Delete user', 'POST', '/users/{{user_id}}/delete'),
    ],
    'Payments' => [
        req('List payments', 'GET', '/payments', ['query' => ['per_page' => 20, 'filter' => 'month']]),
        req('Show payment', 'GET', '/payments/{{payment_id}}'),
    ],
    'Payment types (طرق الدفع)' => [
        req('List payment types', 'GET', '/payment-types', ['query' => ['per_page' => 20, 'contract_type' => 'housing']]),
        req('Create payment type', 'POST', '/payment-types', [
            'body' => [
                'name_ar' => 'دفع شهري',
                'name_en' => 'Monthly Payment',
                'contract_type' => 'housing',
            ],
        ]),
        req('Show payment type', 'GET', '/payment-types/{{payment_type_id}}'),
        req('Update payment type', 'POST', '/payment-types/{{payment_type_id}}', [
            'body' => ['name_ar' => 'دفع شهري محدّث', 'name_en' => 'Monthly Updated'],
        ]),
        req('Delete payment type', 'POST', '/payment-types/{{payment_type_id}}/delete'),
    ],
    'Finance — expenses' => [
        req('List expenses', 'GET', '/finance/expenses', ['query' => ['created_at' => 'month']]),
        req('Create expense', 'POST', '/finance/expenses', [
            'body' => ['amount' => 250.5, 'notes' => 'مصروف مكتب', 'employee_id' => 1],
        ]),
        req('Show expense', 'GET', '/finance/expenses/{{expense_id}}'),
        req('Update expense', 'PUT', '/finance/expenses/{{expense_id}}', [
            'body' => ['amount' => 300, 'notes' => 'مصروف محدّث'],
        ]),
        req('Delete expense', 'DELETE', '/finance/expenses/{{expense_id}}'),
    ],
    'Regions & cities' => [
        req('List regions', 'GET', '/regions', ['query' => ['per_page' => 20]]),
        req('Create region', 'POST', '/regions', ['body' => ['name_ar' => 'منطقة الرياض', 'name_en' => 'Riyadh Region']]),
        req('Show region', 'GET', '/regions/{{id}}'),
        req('Update region', 'POST', '/regions/{{id}}', ['body' => ['name_ar' => 'الرياض']]),
        req('Delete region', 'POST', '/regions/{{id}}/delete'),
        req('List cities', 'GET', '/cities', ['query' => ['per_page' => 20]]),
        req('Create city', 'POST', '/cities', ['body' => ['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'region_id' => 1]]),
        req('Show city', 'GET', '/cities/{{id}}'),
        req('Update city', 'POST', '/cities/{{id}}', ['body' => ['name_ar' => 'الرياض']]),
        req('Delete city', 'POST', '/cities/{{id}}/delete'),
    ],
    'Real estates' => [
        req('List real estates', 'GET', '/real-estates', ['query' => ['per_page' => 20, 'contract_type' => 'housing']]),
        req('Show real estate', 'GET', '/real-estates/{{id}}'),
        req('List real estate types', 'GET', '/real-estate-types'),
        req('Create real estate type', 'POST', '/real-estate-types', ['body' => ['name_ar' => 'شقة', 'name_en' => 'Apartment']]),
        req('Update real estate type', 'POST', '/real-estate-types/{{id}}', ['body' => []]),
        req('Delete real estate type', 'POST', '/real-estate-types/{{id}}/delete'),
        req('List real estate usages', 'GET', '/real-estate-usages', ['query' => ['per_page' => 20]]),
        req('Create real estate usage', 'POST', '/real-estate-usages', ['body' => ['name_ar' => 'سكني', 'name_en' => 'Residential']]),
        req('Show real estate usage', 'GET', '/real-estate-usages/{{id}}'),
        req('Update real estate usage', 'POST', '/real-estate-usages/{{id}}', ['body' => []]),
        req('Delete real estate usage', 'POST', '/real-estate-usages/{{id}}/delete'),
    ],
    'Units' => [
        req('List units', 'GET', '/unit-real-estates', ['query' => ['per_page' => 20, 'user_id' => 1]]),
        req('Create unit', 'POST', '/unit-real-estates', [
            'body' => [
                'user_id' => 1,
                'real_estates_units_id' => 1,
                'unit_number' => '101',
                'unit_area' => '120',
                'unit_type_id' => 1,
                'unit_usage_id' => 1,
            ],
        ]),
        req('Update unit', 'POST', '/unit-real-estates/{{id}}', ['body' => ['unit_number' => '102']]),
        req('Delete unit', 'POST', '/unit-real-estates/{{id}}/delete'),
        req('Unit types — search', 'GET', '/unit-types/search', ['query' => ['q' => 'شقة']]),
        req('Unit types — create form', 'GET', '/unit-types/create'),
        req('List unit types', 'GET', '/unit-types', ['query' => ['per_page' => 20]]),
        req('Create unit type', 'POST', '/unit-types', [
            'body' => ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'contract_type' => 'housing', 'rooms' => 'Room'],
        ]),
        req('Show unit type', 'GET', '/unit-types/{{id}}'),
        req('Update unit type', 'POST', '/unit-types/{{id}}', ['body' => ['name_ar' => 'شقة محدّثة']]),
        req('Delete unit type', 'POST', '/unit-types/{{id}}/delete'),
        req('Unit usages — create form', 'GET', '/unit-usages/create'),
        req('List unit usages', 'GET', '/unit-usages', ['query' => ['per_page' => 20]]),
        req('Create unit usage', 'POST', '/unit-usages', ['body' => ['name_ar' => 'سكني', 'name_en' => 'Residential']]),
        req('Show unit usage', 'GET', '/unit-usages/{{id}}'),
        req('Update unit usage', 'POST', '/unit-usages/{{id}}', ['body' => []]),
        req('Delete unit usage', 'POST', '/unit-usages/{{id}}/delete'),
    ],
    'Roles & permissions' => [
        req('Roles — create form', 'GET', '/roles/create'),
        req('List roles', 'GET', '/roles', ['query' => ['per_page' => 20]]),
        req('Create role', 'POST', '/roles', ['body' => ['name' => 'manager', 'title_ar' => 'مدير', 'title_en' => 'Manager', 'is_active' => true]]),
        req('Show role', 'GET', '/roles/{{id}}'),
        req('Update role', 'POST', '/roles/{{id}}', ['body' => ['title_ar' => 'مدير النظام']]),
        req('Assign permissions', 'POST', '/roles/{{id}}/assign-permissions', ['body' => ['permission_ids' => [1, 2, 3]]]),
        req('Delete role', 'POST', '/roles/{{id}}/delete'),
        req('Permissions by section', 'GET', '/permissions/by-section'),
        req('Permissions — create form', 'GET', '/permissions/create'),
        req('List permissions', 'GET', '/permissions', ['query' => ['per_page' => 20]]),
        req('Create permission', 'POST', '/permissions', ['body' => ['section' => 'orders', 'actions' => ['view', 'edit']]]),
        req('Show permission', 'GET', '/permissions/{{id}}'),
        req('Update permission', 'POST', '/permissions/{{id}}', ['body' => []]),
        req('Delete permission', 'POST', '/permissions/{{id}}/delete'),
    ],
    'Contract config' => [
        req('Active contract statuses', 'GET', '/contract-statuses/active'),
        req('List contract statuses', 'GET', '/contract-statuses', ['query' => ['per_page' => 20]]),
        req('Create contract status', 'POST', '/contract-statuses', [
            'body' => ['name' => 'قيد المعالجة', 'color' => '#FFA500', 'is_active' => true],
        ]),
        req('Show contract status', 'GET', '/contract-statuses/{{id}}'),
        req('Update contract status', 'POST', '/contract-statuses/{{id}}', ['body' => ['name' => 'مكتمل']]),
        req('Delete contract status', 'POST', '/contract-statuses/{{id}}/delete'),
        req('List contract periods', 'GET', '/contract-periods', ['query' => ['per_page' => 20]]),
        req('Contract periods create helper', 'POST', '/contract-periods/create', ['body' => []]),
        req('Create contract period', 'POST', '/contract-periods', ['body' => ['period' => 'سنة واحدة', 'years' => 1]]),
        req('Show contract period', 'GET', '/contract-periods/{{id}}'),
        req('Update contract period', 'POST', '/contract-periods/{{id}}', ['body' => []]),
        req('Delete contract period', 'POST', '/contract-periods/{{id}}/delete'),
        req('Contract WhatsApp list', 'GET', '/contract-whatsapp', ['query' => ['per_page' => 20]]),
        req('Contract WhatsApp — complete', 'POST', '/contract-whatsapp/complete', ['body' => []]),
        req('Contract WhatsApp — incomplete', 'POST', '/contract-whatsapp/incomplete', ['body' => []]),
    ],
    'Coupons & paperwork & FAQs' => [
        req('List coupons', 'GET', '/coupons', ['query' => ['per_page' => 20]]),
        req('Create coupon', 'POST', '/coupons', [
            'body' => ['code' => 'SAVE10', 'discount' => 10, 'type' => 'percentage', 'is_active' => true],
        ]),
        req('Show coupon', 'GET', '/coupons/{{id}}'),
        req('Update coupon', 'POST', '/coupons/{{id}}', ['body' => []]),
        req('Activate coupon', 'POST', '/coupons/{{id}}/activate'),
        req('Deactivate coupon', 'POST', '/coupons/{{id}}/inactive'),
        req('Delete coupon', 'POST', '/coupons/{{id}}/delete'),
        req('List paperworks', 'GET', '/paperworks', ['query' => ['per_page' => 20]]),
        req('Create paperwork', 'POST', '/paperworks', ['body' => ['name_ar' => 'مستند', 'name_en' => 'Document']]),
        req('Show paperwork', 'GET', '/paperworks/{{id}}'),
        req('Update paperwork', 'POST', '/paperworks/{{id}}', ['body' => []]),
        req('Delete paperwork', 'POST', '/paperworks/{{id}}/delete'),
        req('List FAQs', 'GET', '/faqs', ['query' => ['per_page' => 20]]),
        req('Create FAQ', 'POST', '/faqs', [
            'body' => [
                'title_ar' => 'سؤال شائع',
                'title_en' => 'FAQ',
                'answer_ar' => 'الإجابة',
                'answer_en' => 'Answer',
            ],
        ]),
        req('Show FAQ', 'GET', '/faqs/{{id}}'),
        req('Update FAQ', 'POST', '/faqs/{{id}}', ['body' => []]),
        req('Delete FAQ', 'POST', '/faqs/{{id}}/delete'),
    ],
    'Customer messages (رسائل التطبيق للعميل)' => [
        req('Overview', 'GET', '/customer-messages/overview'),
        req('All (tree)', 'GET', '/customer-messages/all'),
        req('Create form', 'GET', '/customer-messages/create'),
        req('List messages', 'GET', '/customer-messages', ['query' => ['per_page' => 20]]),
        req('Create message', 'POST', '/customer-messages', [
            'body' => [
                'message_alert_section_id' => 1,
                'message_alert_section_item_id' => 1,
                'message' => 'نص رسالة توضيحية للعميل',
            ],
        ]),
        req('Show message', 'GET', '/customer-messages/{{message_alert_id}}'),
        req('Update message', 'POST', '/customer-messages/{{message_alert_id}}', [
            'body' => ['message' => 'نص محدّث'],
        ]),
        req('Delete message', 'POST', '/customer-messages/{{message_alert_id}}/delete'),
    ],
    'Message alerts — sections & items' => [
        req('Section options', 'GET', '/message-alert-sections/options/list', ['query' => ['type' => 'client']]),
        req('List sections', 'GET', '/message-alert-sections', ['query' => ['type' => 'client', 'per_page' => 20]]),
        req('Create section', 'POST', '/message-alert-sections', [
            'body' => ['name_ar' => 'قسم جديد', 'name_en' => 'New section', 'sort_order' => 0, 'type' => 'client'],
        ]),
        req('Show section', 'GET', '/message-alert-sections/{{message_alert_section_id}}'),
        req('Update section', 'POST', '/message-alert-sections/{{message_alert_section_id}}', ['body' => ['name_ar' => 'قسم محدّث']]),
        req('Delete section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/delete'),
        req('Items under section', 'GET', '/message-alert-sections/{{message_alert_section_id}}/items', ['query' => ['per_page' => 20]]),
        req('Create item under section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/items', [
            'body' => ['name_ar' => 'عنصر', 'name_en' => 'Item', 'sort_order' => 0],
        ]),
        req('Section item options', 'GET', '/message-alert-section-items/options/list', [
            'query' => ['message_alert_section_id' => '{{message_alert_section_id}}', 'type' => 'client'],
        ]),
        req('List section items (flat)', 'GET', '/message-alert-section-items', [
            'query' => ['type' => 'client', 'message_alert_section_id' => '{{message_alert_section_id}}'],
        ]),
        req('Create section item', 'POST', '/message-alert-section-items', [
            'body' => ['message_alert_section_id' => 1, 'name_ar' => 'عنصر', 'sort_order' => 0],
        ]),
        req('Show section item', 'GET', '/message-alert-section-items/{{message_alert_section_item_id}}'),
        req('Update section item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}', ['body' => []]),
        req('Delete section item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}/delete'),
    ],
    'Message alerts (legacy routes)' => [
        req('Types overview', 'GET', '/message-alerts/types'),
        req('All messages', 'GET', '/message-alerts/all', ['query' => ['type' => 'client']]),
        req('Client — create form', 'GET', '/message-alerts/client/create'),
        req('Client — list', 'GET', '/message-alerts/client', ['query' => ['per_page' => 20]]),
        req('Client — create', 'POST', '/message-alerts/client', [
            'body' => [
                'message_alert_section_id' => 1,
                'message_alert_section_item_id' => 1,
                'message' => 'رسالة للعميل',
            ],
        ]),
        req('Client — show', 'GET', '/message-alerts/client/{{message_alert_id}}'),
        req('Client — update', 'POST', '/message-alerts/client/{{message_alert_id}}', ['body' => ['message' => 'محدّث']]),
        req('Client — delete', 'POST', '/message-alerts/client/{{message_alert_id}}/delete'),
    ],
    'Legal content (terms & privacy)' => [
        req('Legal pages (both)', 'GET', '/content/legal-pages'),
        req('Get terms', 'GET', '/content/terms-and-conditions'),
        req('Update terms', 'POST', '/content/terms-and-conditions', [
            'body' => [
                'description_ar' => '<p>الشروط والأحكام</p>',
                'description_en' => '<p>Terms and conditions</p>',
            ],
        ]),
        req('Get privacy', 'GET', '/content/privacy'),
        req('Update privacy', 'POST', '/content/privacy', [
            'body' => [
                'description_ar' => '<p>سياسة الخصوصية</p>',
                'description_en' => '<p>Privacy policy</p>',
            ],
        ]),
    ],
    'Blogs' => [
        req('List blogs', 'GET', '/blogs', ['query' => ['per_page' => 10, 'page' => 1]]),
        req('Blog statistics', 'GET', '/blogs/statistics'),
        req('Create blog', 'POST', '/blogs', [
            'body' => [
                'title' => 'عنوان المقال',
                'description' => 'محتوى المقال',
                'status' => 'published',
                'meta_title' => 'SEO title',
                'meta_description' => 'SEO description',
            ],
        ]),
        req('Show blog', 'GET', '/blogs/{{id}}'),
        req('Update blog', 'PUT', '/blogs/{{id}}', [
            'body' => ['title' => 'عنوان محدّث', 'description' => 'محتوى محدّث', 'status' => 'published'],
        ]),
        req('Toggle blog active', 'POST', '/blogs/{{id}}/toggle-active', ['body' => []]),
        req('Delete blog', 'DELETE', '/blogs/{{id}}'),
    ],
    'Ads' => [
        req('List ads', 'GET', '/ads', ['query' => ['per_page' => 20]]),
        req('Create ad', 'POST', '/ads', ['body' => ['title_ar' => 'إعلان', 'title_en' => 'Ad', 'is_active' => true]]),
        req('Show ad', 'GET', '/ads/{{id}}'),
        req('Update ad', 'POST', '/ads/{{id}}', ['body' => []]),
        req('Delete ad', 'POST', '/ads/{{id}}/delete'),
    ],
];

$items = [];
foreach ($folders as $folderName => $requests) {
    $items[] = [
        'name' => $folderName,
        'item' => $requests,
    ];
}

$collectionId = uuid4();
$environmentId = uuid4();

$collectionVars = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
    ['key' => 'employee_token', 'value' => '', 'type' => 'secret'],
    ['key' => 'employee_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'comment_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'user_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'payment_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'payment_type_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'expense_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_section_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'message_alert_section_item_id', 'value' => '1', 'type' => 'default'],
];

$collection = [
    'info' => [
        '_postman_id' => $collectionId,
        'name' => 'AQDI Admin API',
        'description' => "Complete Admin API under /api/admin.\n\n1. Import AQDI-Admin-API.postman_environment.json\n2. Set baseUrl\n3. Run Employee login, copy data.token to employee_token",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => $collectionVars,
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{employee_token}}', 'type' => 'string'],
        ],
    ],
];

$environment = [
    'id' => $environmentId,
    'name' => 'AQDI Admin Local',
    'values' => array_map(
        static fn (array $v) => [
            'key' => $v['key'],
            'value' => $v['value'],
            'type' => $v['type'] ?? 'default',
            'enabled' => true,
        ],
        $collectionVars
    ),
    '_postman_variable_scope' => 'environment',
    '_postman_exported_at' => gmdate('c'),
    '_postman_exported_using' => 'Postman/11.0.0',
];

$postmanDir = $basePath.'/postman';
if (! is_dir($postmanDir)) {
    mkdir($postmanDir, 0755, true);
}

$collectionPath = $postmanDir.'/AQDI-Admin-API.postman_collection.json';
$environmentPath = $postmanDir.'/AQDI-Admin-API.postman_environment.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

file_put_contents(
    $environmentPath,
    json_encode($environment, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

$requestCount = array_sum(array_map(fn ($f) => count($f), $folders));

echo "Wrote {$collectionPath}\n";
echo "Wrote {$environmentPath}\n";
echo "Folders: ".count($folders).", Requests: {$requestCount}\n";
