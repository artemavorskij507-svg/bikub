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

    'mapbox' => [
        'token' => env('MAPBOX_TOKEN'),
    ],

    'stripe' => [
        'key' => env('STRIPE_PUBLISHABLE_KEY', env('STRIPE_KEY')),
        'secret' => env('STRIPE_SECRET_KEY', env('STRIPE_SECRET')),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],

    'vipps' => [
        'base_url' => env('VIPPS_BASE_URL', 'https://api.vipps.no'),
        'client_id' => env('VIPPS_CLIENT_ID'),
        'client_secret' => env('VIPPS_CLIENT_SECRET'),
        'subscription_key' => env('VIPPS_SUBSCRIPTION_KEY'),
        'merchant_serial_number' => env('VIPPS_MERCHANT_SERIAL_NUMBER'),
    ],

    // OpenAI – cloud AI fallback / generation
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    // n8n – webhooks for external automation (including classifieds expirations)
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'enabled' => env('N8N_WEBHOOK_ENABLED', false),
        'classifieds_expired_webhook' => env('N8N_CLASSIFIEDS_EXPIRED_WEBHOOK'),
    ],

];
