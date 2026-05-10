<?php

return [
    'endpoints' => env('TASK_WEBHOOKS', '')
        ? array_filter(array_map('trim', explode(',', env('TASK_WEBHOOKS'))))
        : [],

    'secret' => env('WEBHOOK_SECRET', env('APP_KEY')),

    'retries' => [
        30,   // 30 seconds
        120,  // 2 minutes
        300,  // 5 minutes
    ],

    'timeout' => env('WEBHOOK_TIMEOUT', 5),

    'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),

    // Signature verification secrets for external providers
    'providers' => [
        'stripe' => [
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'n8n' => [
            'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
        ],
    ],
];
