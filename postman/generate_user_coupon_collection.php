<?php

/**
 * Custom user coupon (discount setup + login notification).
 * Run: php postman/generate_user_coupon_collection.php
 */

$out = __DIR__.'/AQDI-Admin-User-Coupon.postman_collection.json';

$authHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'],
];

$jsonHeaders = array_merge($authHeaders, [
    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
]);

$userHeaders = [
    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ['key' => 'Authorization', 'value' => 'Bearer {{user_token}}', 'type' => 'text'],
];

$loginEvent = [[
    'listen' => 'test',
    'script' => [
        'type' => 'text/javascript',
        'exec' => [
            'const json = pm.response.json();',
            'const token = json?.data?.token ?? json?.token;',
            'if (token) {',
            '  pm.collectionVariables.set("employee_token", token);',
            '  pm.environment.set("employee_token", token);',
            '}',
        ],
    ],
]];

$userLoginEvent = [[
    'listen' => 'test',
    'script' => [
        'type' => 'text/javascript',
        'exec' => [
            'const json = pm.response.json();',
            'const token = json?.data?.token ?? json?.token;',
            'if (token) {',
            '  pm.collectionVariables.set("user_token", token);',
            '  pm.environment.set("user_token", token);',
            '}',
            'const couponId = json?.data?.login_notification?.user_coupon_id;',
            'if (couponId) { pm.collectionVariables.set("user_coupon_id", String(couponId)); }',
            'const code = json?.data?.login_notification?.code_coupon;',
            'if (code) { pm.collectionVariables.set("code_coupon", code); }',
        ],
    ],
]];

$assignEvent = [[
    'listen' => 'test',
    'script' => [
        'type' => 'text/javascript',
        'exec' => [
            'const json = pm.response.json();',
            'const id = json?.data?.id;',
            'const code = json?.data?.secret_code ?? json?.data?.code_coupon;',
            'if (id) { pm.collectionVariables.set("user_coupon_id", String(id)); }',
            'if (code) { pm.collectionVariables.set("code_coupon", code); }',
        ],
    ],
]];

$collection = [
    'info' => [
        'name' => 'AQDI Admin — User Custom Coupon',
        'description' => 'Assign a secret coupon to a client (first-year fees) and verify the login notification popup.',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'user_token', 'value' => ''],
        ['key' => 'user_id', 'value' => '1'],
        ['key' => 'user_coupon_id', 'value' => '1'],
        ['key' => 'code_coupon', 'value' => ''],
        ['key' => 'contract_uuid', 'value' => ''],
        ['key' => 'user_mobile', 'value' => '0512345678'],
        ['key' => 'user_password', 'value' => 'password123'],
    ],
    'item' => [
        [
            'name' => '1. Employee login',
            'event' => $loginEvent,
            'request' => [
                'method' => 'POST',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
                    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'email' => 'admin@example.com',
                        'password' => 'password',
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/admin/employees/login',
            ],
        ],
        [
            'name' => '2. Assign custom coupon',
            'event' => $assignEvent,
            'request' => [
                'method' => 'POST',
                'header' => $jsonHeaders,
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'type' => 'percentage',
                        'value' => 10,
                        'applies_to' => 'all',
                        'expires_at' => '2026-12-31',
                        'reason' => 'عميل مميز',
                        'notify_on_login' => true,
                        'notification_message' => 'تهانينا! حصلت على خصم خاص على رسوم السنة الأولى.',
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/admin/users/{{user_id}}/coupons',
                'description' => 'type: percentage|fixed. applies_to: all|housing|commercial. Discount is on first-year fees only. Returns secret_code.',
            ],
        ],
        [
            'name' => '3. List user coupons',
            'request' => [
                'method' => 'GET',
                'header' => $authHeaders,
                'url' => '{{baseUrl}}/api/admin/users/{{user_id}}/coupons',
            ],
        ],
        [
            'name' => '4. Client login (shows login_notification)',
            'event' => $userLoginEvent,
            'request' => [
                'method' => 'POST',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
                    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'mobile' => '{{user_mobile}}',
                        'password' => '{{user_password}}',
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/v2/auth/login',
                'description' => 'data.login_notification is the popup: title, message, code_coupon.',
            ],
        ],
        [
            'name' => '5. My pending coupons',
            'request' => [
                'method' => 'GET',
                'header' => $userHeaders,
                'url' => '{{baseUrl}}/api/v2/coupons/mine',
            ],
        ],
        [
            'name' => '6. Apply secret coupon on a contract',
            'request' => [
                'method' => 'POST',
                'header' => array_merge($userHeaders, [
                    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                ]),
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'code_coupon' => '{{code_coupon}}',
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/v2/Coupon/{{contract_uuid}}',
            ],
        ],
        [
            'name' => '7. Acknowledge login notification',
            'request' => [
                'method' => 'POST',
                'header' => array_merge($userHeaders, [
                    ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                ]),
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'user_coupon_id' => '{{user_coupon_id}}',
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/v2/coupons/login-notification/ack',
            ],
        ],
        [
            'name' => '8. Deactivate coupon',
            'request' => [
                'method' => 'POST',
                'header' => $jsonHeaders,
                'body' => [
                    'mode' => 'raw',
                    'raw' => '{}',
                    'options' => ['raw' => ['language' => 'json']],
                ],
                'url' => '{{baseUrl}}/api/admin/users/{{user_id}}/coupons/{{user_coupon_id}}/deactivate',
            ],
        ],
    ],
];

file_put_contents($out, json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
echo "Wrote {$out}\n";
