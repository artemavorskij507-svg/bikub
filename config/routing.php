<?php

return [
    'default_provider' => env('ROUTING_PROVIDER', 'null'),

    'osrm' => [
        'base_url' => env('OSRM_BASE_URL'),
        'timeout_seconds' => (int) env('OSRM_TIMEOUT_SECONDS', 3),
        'enabled' => (bool) env('OSRM_ENABLED', false),
    ],

    'shadow_mode' => (bool) env('ROUTING_SHADOW_MODE', true),
    'store_snapshots' => (bool) env('ROUTING_STORE_SNAPSHOTS', true),
];

