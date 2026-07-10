<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    // 'loop' => [
    //     'api_key' => env('LOOP_API_KEY'),
    // ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'firebase' => [
        'secret' => env('FIREBASE_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => '',
    ],

    'moyasar' => [
        'base_url' => env('MOYASAR_BASE_URL', 'https://api.moyasar.com'),
        'secret_key' => env('MOYASAR_SECRET_KEY'),
        'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
        'currency' => env('MOYASAR_CURRENCY', 'SAR'),
        'payment_frontend_url' => rtrim((string) env('PAYMENT_FRONTEND_URL', 'http://localhost:3000'), '/'),
        'payment_success_url_template' => env('PAYMENT_SUCCESS_URL_TEMPLATE'),
        'payment_error_url_template' => env('PAYMENT_ERROR_URL_TEMPLATE'),
        // Optional deep-link / universal-link templates for the mobile app only.
        'payment_app_success_url_template' => env('PAYMENT_APP_SUCCESS_URL_TEMPLATE'),
        'payment_app_error_url_template' => env('PAYMENT_APP_ERROR_URL_TEMPLATE'),
    ],

];
