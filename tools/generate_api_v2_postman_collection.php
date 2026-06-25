<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-API-V2.postman_collection.json
 * covering all routes in routes/api_v2.php.
 *
 * Run: php tools/generate_api_v2_postman_collection.php
 */

$basePath = dirname(__DIR__);

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function hdr(bool $json = false): array
{
    $h = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    return $h;
}

/**
 * @param  array<string, scalar|null>  $query
 * @return array<string, mixed>
 */
function buildUrl(string $path, array $query = []): array
{
    $path = '/'.trim($path, '/');
    $segments = array_values(array_filter(explode('/', $path)));
    $apiPath = array_merge(['api', 'v2'], $segments);

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

    $raw = '{{baseUrl}}/'.implode('/', $apiPath);
    if ($queryItems !== []) {
        $raw .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }

    $url = [
        'raw' => $raw,
        'host' => ['{{baseUrl}}'],
        'path' => $apiPath,
    ];

    if ($queryItems !== []) {
        $url['query'] = $queryItems;
    }

    return $url;
}

/**
 * @param  array<string, mixed>  $opts
 * @return array<string, mixed>
 */
function req(string $name, string $method, string $path, array $opts = []): array
{
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);
    $public = (bool) ($opts['public'] ?? false);

    $request = [
        'method' => $method,
        'header' => hdr($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)),
        'url' => buildUrl($path, $query),
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    if ($public) {
        $request['auth'] = ['type' => 'noauth'];
    }

    if (! empty($opts['description'])) {
        $request['description'] = $opts['description'];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if (! empty($opts['event'])) {
        $item['event'] = $opts['event'];
    }

    return $item;
}

/** @return list<array<string, mixed>> */
function generalPublicEndpoints(): array
{
    $paths = [
        'cities' => 'List cities',
        'regions' => 'List regions',
        'terms-and-conditions' => 'Terms and conditions',
        'privacy' => 'Privacy policy',
        'common-questions' => 'FAQ',
        'bank-accounts' => 'Bank accounts',
        'services-pricing' => 'Services pricing',
        'paperwork' => 'Paperwork',
        'real-estat-type' => 'Real estate types',
        'real-estat-usage' => 'Real estate usages',
        'units-types' => 'Unit types',
        'units-usage' => 'Unit usages',
        'payments-types' => 'Payment types',
        'contract-periods' => 'Contract periods',
        'settings' => 'App settings',
        'cover' => 'Cover image',
    ];

    $items = [];
    foreach ($paths as $path => $name) {
        $items[] = req($name, 'GET', "/{$path}", ['public' => true]);
    }

    return $items;
}

$realEstateStep1Body = [
    'contract_type' => 'housing',
    'contract_ownership' => 'owner',
    'instrument_type' => 'electronic',
    'instrument_history' => '1440-01-01',
    'type_instrument_history' => 'hijri',
    'property_place_id' => 1,
    'property_city_id' => 1,
    'neighborhood' => 'حي النخيل',
    'street' => 'شارع الملك',
    'building_number' => '12',
    'postal_code' => '12345',
    'extra_figure' => '1234',
    'latitude' => 24.7136,
    'longitude' => 46.6753,
];

$realEstateStep2Body = [
    'id' => '{{real_estate_id}}',
    'type_dob_property_owner' => 'hijri',
    'name_owner' => 'اسم المالك',
    'property_owner_id_num' => '1234567890',
    'property_owner_dob_day' => '15',
    'property_owner_dob_month' => '06',
    'property_owner_dob_year' => '1410',
    'property_owner_mobile' => '0512345678',
    'property_owner_iban' => 'SA0380000000608010167500',
];

$realEstateUpdateStep1Body = array_merge(
    ['id' => '{{real_estate_id}}'],
    $realEstateStep1Body
);

$loginTestScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const token = json?.data?.token || json?.token;',
                'if (token) {',
                '    pm.collectionVariables.set("token", token);',
                '    console.log("Saved token to collection variable.");',
                '}',
                'const contractId = json?.data?.contract?.id || json?.data?.id;',
                'if (contractId) {',
                '    pm.collectionVariables.set("contract_id", String(contractId));',
                '}',
                'const uuid = json?.data?.contract?.uuid || json?.data?.uuid;',
                'if (uuid) {',
                '    pm.collectionVariables.set("contract_uuid", uuid);',
                '}',
                'const realId = json?.data?.real_estate?.id || json?.data?.id;',
                'if (realId && pm.request.url.path.includes("realstate")) {',
                '    pm.collectionVariables.set("real_estate_id", String(realId));',
                '}',
            ],
        ],
    ],
];

$folders = [
    '01 — Public — General' => generalPublicEndpoints(),

    '02 — Public — Instruction images' => [
        req('List instruction images', 'GET', '/instruction-images', ['public' => true]),
        req('Show instruction image by key', 'GET', '/instruction-images/{{instruction_image_key}}', [
            'public' => true,
            'description' => 'Replace `instruction_image_key` with a valid key (e.g. step1-instrument).',
        ]),
    ],

    '03 — Public — Tenant roles' => [
        req('List tenant roles', 'GET', '/tenant-roles', [
            'public' => true,
            'description' => 'Only `GET /tenant-roles` is implemented. Other tenant-role routes in api_v2.php are not wired in the controller.',
        ]),
    ],

    '04 — Public — Auth' => [
        req('Login', 'POST', '/auth/login', [
            'public' => true,
            'body' => [
                'mobile' => '0512345678',
                'password' => 'password123',
                'fcm_token' => 'optional-fcm-token',
            ],
            'description' => 'On success, saves `token` to collection variables (test script).',
            'event' => $loginTestScript,
        ]),
        req('Signup', 'POST', '/auth/signup', [
            'public' => true,
            'body' => [
                'fname' => 'محمد',
                'mobile' => '0599999999',
                'email' => 'user@example.com',
                'password' => 'password123',
                'fcm_token' => 'optional-fcm-token',
            ],
        ]),
        req('Verify account (OTP)', 'POST', '/auth/verification', [
            'public' => true,
            'body' => [
                'mobile' => '00966599999999',
                'verification_code' => '1234',
            ],
        ]),
        req('Resend verification OTP', 'POST', '/auth/resend', [
            'public' => true,
            'body' => ['mobile' => '0512345678'],
        ]),
        req('Forgot password', 'POST', '/auth/forgot-password', [
            'public' => true,
            'body' => ['mobile' => '0512345678'],
        ]),
        req('Validate reset password code', 'POST', '/auth/reset-password-code', [
            'public' => true,
            'body' => [
                'mobile' => '00966512345678',
                'code' => '123456',
            ],
        ]),
        req('Reset password', 'POST', '/auth/reset-password', [
            'public' => true,
            'body' => [
                'mobile' => '00966512345678',
                'code' => '123456',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ],
        ]),
        req('Google OAuth callback', 'POST', '/auth/google/callback', [
            'public' => true,
            'body' => ['id_token' => 'google-id-token'],
            'description' => 'Payload depends on Google Sign-In integration.',
        ]),
    ],

    '05 — Auth — Profile & account' => [
        req('Logout', 'POST', '/auth/logout'),
        req('Get profile', 'GET', '/profile'),
        req('Update profile', 'POST', '/profile', [
            'body' => [
                'fname' => 'محمد',
                'email' => 'updated@example.com',
                'mobile' => '0512345678',
            ],
            'description' => 'Optional `photo` file — use form-data in Postman for image upload.',
        ]),
        req('Update password', 'POST', '/update/password', [
            'body' => [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ],
        ]),
        req('Update FCM token', 'POST', '/fcm', [
            'body' => ['fcm_token' => 'device-fcm-token'],
        ]),
        req('Notifications', 'GET', '/notifications'),
        req('Deactivate account', 'POST', '/user/deactivate'),
    ],

    '06 — Contracts — Start & steps' => [
        req('Start contract', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'old_handwritten',
                'is_real' => false,
            ],
            'description' => 'Creates a new contract. Saves contract_id/uuid via test script when present in response.',
            'event' => $loginTestScript,
        ]),
        req('Start contract (from saved real estate)', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'electronic',
                'is_real' => true,
                'real_id' => '{{real_estate_id}}',
                'real_units_id' => '{{unit_id}}',
            ],
        ]),
        req('Step 1 — property details', 'POST', '/contract/step1', [
            'body' => [
                'id' => '{{contract_id}}',
                'number_of_floors' => 2,
                'property_type_id' => 1,
                'property_usages_id' => 1,
                'number_of_units_in_realestate' => 4,
            ],
            'description' => 'File fields (image_instrument_from_the_front/back) use multipart/form-data.',
        ]),
        req('Step 2 — address', 'POST', '/contract/step2', [
            'body' => [
                'id' => '{{contract_id}}',
                'property_place_id' => 1,
                'property_city_id' => 1,
                'neighborhood' => 'حي النخيل',
                'street' => 'شارع الملك',
                'building_number' => '12',
                'postal_code' => '12345',
                'extra_figure' => '1234',
            ],
        ]),
        req('Step 3 — owner', 'POST', '/contract/step3', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'property_owner_iban' => 'SA0380000000608010167500',
                'notes_edits' => 'ملاحظات التعديلات — حقل اختياري',
            ],
            'description' => '`add_legal_agent_of_owner` optional — defaults to false. Agent/owner file fields use multipart.',
        ]),
        req('Step 3 — owner (with legal agent)', 'POST', '/contract/step3', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'property_owner_iban' => 'SA0380000000608010167500',
                'add_legal_agent_of_owner' => true,
                'id_num_of_property_owner_agent' => '1098765432',
                'dob_of_property_owner_agent_day' => '01',
                'dob_of_property_owner_agent_month' => '01',
                'dob_of_property_owner_agent_year' => '1415',
                'mobile_of_property_owner_agent' => '0598765432',
            ],
            'description' => 'Requires `copy_of_the_authorization_or_agency` file when no prior upload exists.',
        ]),
        req('Step 4 — tenant', 'POST', '/contract/step4', [
            'body' => [
                'id' => '{{contract_id}}',
                'tenant_name' => 'اسم المستأجر',
                'type_tenant_dob' => 'hijri',
                'tenant_id_num' => '1098765432',
                'tenant_dob_day' => '01',
                'tenant_dob_month' => '01',
                'tenant_dob_year' => '1415',
                'tenant_mobile' => '0598765432',
            ],
        ]),
        req('Step 5 — unit details', 'POST', '/contract/step5', [
            'body' => [
                'id' => '{{contract_id}}',
                'unit_type_id' => 1,
                'unit_usage_id' => 1,
                'unit_number' => '101',
                'unit_area' => '120',
                'kitchen_tank' => 0,
                'furnished' => 0,
                'electricity_meter' => 1,
                'water_meter' => 1,
            ],
        ]),
        req('Step 6 — terms & payment (tenant_role_ids)', 'POST', '/contract/step6', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_contract_starting_date' => 'hijri',
                'contract_starting_date_day' => '01',
                'contract_starting_date_month' => '01',
                'contract_starting_date_year' => '1447',
                'contract_term_in_years' => 1,
                'annual_rent_amount_for_the_unit' => 24000,
                'payment_type_id' => 1,
                'conditions' => false,
                'tenant_roles' => true,
                'tenant_role_ids' => [1, 2],
                'additional_terms' => false,
            ],
        ]),
        req('Step 6 — legacy tenant_role_id', 'POST', '/contract/step6', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_contract_starting_date' => 'hijri',
                'contract_starting_date_day' => '01',
                'contract_starting_date_month' => '01',
                'contract_starting_date_year' => '1447',
                'contract_term_in_years' => 1,
                'annual_rent_amount_for_the_unit' => 24000,
                'payment_type_id' => 1,
                'conditions' => false,
                'tenant_roles' => true,
                'tenant_role_id' => 1,
                'additional_terms' => false,
            ],
        ]),
        req('Set contract draft', 'POST', '/contract/draft', [
            'body' => [
                'id' => '{{contract_id}}',
                'is_draft' => true,
            ],
        ]),
    ],

    '07 — Contracts — Uncompleted' => [
        req('Check uncompleted contract', 'GET', '/contract/check-uncompleted-contract'),
        req('Get uncompleted contract step', 'POST', '/contract/uncompleted-contract', [
            'body' => ['uuid' => '{{contract_uuid}}'],
        ]),
    ],

    '08 — Contracts — Listing & search' => [
        req('List my contracts', 'GET', '/contracts'),
        req('Show contract', 'GET', '/contracts/{{contract_id}}'),
        req('Delete contract', 'DELETE', '/contracts/{{contract_id}}'),
        req('Get contracts by uuid', 'GET', '/getContracts/{{contract_uuid}}'),
        req('Search contracts', 'GET', '/search/{{search_term}}'),
        req('Financial summary', 'GET', '/financial/{{contract_uuid}}'),
        req('Finance summary (alias)', 'GET', '/finance-summary/{{contract_uuid}}'),
    ],

    '09 — Real estate — Create & update' => [
        req('Step 1 — create (property + location)', 'POST', '/realstate/step1', [
            'body' => $realEstateStep1Body,
            'description' => 'Multipart: image_instrument, image_address, endowment/trusteeship files. Alias prefix: `/realState/...`',
            'event' => $loginTestScript,
        ]),
        req('Step 2 — owner data', 'POST', '/realstate/step2', [
            'body' => $realEstateStep2Body,
            'description' => '`add_legal_agent_of_owner` optional — defaults to false. Multipart: copy_of_the_authorization_or_agency when agent is true.',
        ]),
        req('Update step 1', 'POST', '/realstate/update/step1', [
            'body' => $realEstateUpdateStep1Body,
            'description' => 'Multipart file fields same as step 1 create.',
        ]),
        req('Update step 2 — owner data', 'POST', '/realstate/update/step2', [
            'body' => $realEstateStep2Body,
        ]),
        req('Step 3 (deprecated alias of step 2)', 'POST', '/realstate/step3', [
            'body' => $realEstateStep2Body,
            'description' => 'Deprecated — use step2. Kept for backward compatibility.',
        ]),
        req('Update step 3 (deprecated alias)', 'POST', '/realstate/update/step3', [
            'body' => $realEstateStep2Body,
            'description' => 'Deprecated — use update/step2.',
        ]),
    ],

    '10 — Real estate — Read & delete' => [
        req('List my real estates', 'GET', '/realstate/index'),
        req('List all real estates', 'GET', '/realstate/all'),
        req('Show real estate', 'GET', '/realstate/show/{{real_estate_id}}'),
        req('Show real estate units', 'GET', '/realstate/units/{{real_estate_id}}'),
        req('Delete real estate', 'DELETE', '/realstate/delete/{{real_estate_id}}'),
    ],

    '11 — Units' => [
        req('Create unit', 'POST', '/unit/create', [
            'body' => [
                'real_estates_units_id' => '{{real_estate_id}}',
                'unit_type_id' => 1,
                'unit_usage_id' => 1,
                'unit_number' => '101',
                'floor_number' => 1,
                'unit_area' => 120,
                'tootal_rooms' => 3,
                'The_number_of_halls' => 1,
                'The_number_of_kitchens' => 1,
                'The_number_of_toilets' => 2,
                'window_ac' => 1,
                'split_ac' => 1,
                'electricity_meter_number' => 'EM-12345',
                'water_meter_number' => 'WM-67890',
                'kitchen_tank' => false,
                'furnished' => false,
                'type_furnished' => false,
                'electricity_meter' => true,
                'water_meter' => true,
            ],
            'event' => $loginTestScript,
        ]),
        req('List units for real estate', 'GET', '/unit/index/{{real_estate_id}}'),
        req('List all units for real estate', 'GET', '/unit/all/{{real_estate_id}}'),
        req('Show unit', 'GET', '/unit/show/{{unit_id}}'),
        req('Update unit', 'POST', '/unit/update/{{unit_id}}', [
            'body' => [
                'unit_number' => '102',
                'floor_number' => 2,
                'unit_area' => 130,
                'split_ac' => 2,
                'furnished' => true,
            ],
        ]),
        req('Delete unit', 'DELETE', '/unit/delete/{{unit_id}}'),
    ],

    '12 — Saved property' => [
        req('Save property from contract', 'POST', '/save/property', [
            'body' => [
                'contract_id' => '{{contract_id}}',
                'name_real_estate' => 'عقاري المحفوظ',
            ],
        ]),
    ],

    '13 — Coupon' => [
        req('Apply coupon', 'POST', '/Coupon/{{contract_uuid}}', [
            'body' => ['code_coupon' => 'DISCOUNT10'],
        ]),
    ],

    '14 — Payment callbacks' => [
        req('Payment IPN callback', 'POST', '/status/{{contract_uuid}}/success', [
            'public' => true,
            'body' => ['status' => 'paid'],
            'description' => 'ClickPay IPN webhook — no auth. Payload varies by gateway.',
        ]),
        req('Payment return callback', 'POST', '/status/{{contract_uuid}}', [
            'public' => true,
            'body' => [],
            'description' => 'Payment gateway return POST — no auth.',
        ]),
        req('Payment success redirect', 'GET', '/status/success/{{contract_uuid}}', [
            'public' => true,
        ]),
        req('Payment error redirect', 'GET', '/status/error/{{contract_uuid}}', [
            'public' => true,
        ]),
        req('Payment details', 'GET', '/payment/{{contract_uuid}}', [
            'public' => true,
            'description' => 'Documented as payment details; route is outside sanctum middleware group.',
        ]),
    ],
];

$items = [];
foreach ($folders as $folderName => $requests) {
    $items[] = [
        'name' => $folderName,
        'item' => $requests,
    ];
}

$collectionVars = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
    ['key' => 'token', 'value' => '', 'type' => 'secret'],
    ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'contract_uuid', 'value' => '', 'type' => 'default'],
    ['key' => 'real_estate_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'unit_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'search_term', 'value' => '101', 'type' => 'default'],
    ['key' => 'instruction_image_key', 'value' => 'step1-instrument', 'type' => 'default'],
];

$requestCount = array_sum(array_map(fn ($f) => count($f), $folders));

$collection = [
    'info' => [
        '_postman_id' => uuid4(),
        'name' => 'AQDI API V2 (Full)',
        'description' => "Complete Postman collection for all routes in `routes/api_v2.php`.\n\n**Base path:** `{{baseUrl}}/api/v2`\n\n**Auth:** Bearer token (`auth:sanctum`) on protected routes. Public folders override with no auth.\n\n**Quick start:**\n1. Set `baseUrl` (e.g. `http://localhost:8000`)\n2. Run **04 — Public — Auth → Login** — token is saved automatically\n3. Use contract/real-estate flows; IDs saved when responses include them\n\n**Notes:**\n- Real-estate prefix `/realstate` has alias `/realState`\n- File uploads: switch body to `form-data` in Postman\n- `add_legal_agent_of_owner` defaults to `false` when omitted (contract step 3 & real-estate step 2)\n\nGenerated by `php tools/generate_api_v2_postman_collection.php` — {$requestCount} requests.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => $collectionVars,
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{token}}', 'type' => 'string'],
        ],
    ],
];

$postmanDir = $basePath.'/postman';
if (! is_dir($postmanDir)) {
    mkdir($postmanDir, 0755, true);
}

$collectionPath = $postmanDir.'/AQDI-API-V2.postman_collection.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

echo "Wrote {$collectionPath}\n";
echo 'Folders: '.count($folders).", Requests: {$requestCount}\n";
