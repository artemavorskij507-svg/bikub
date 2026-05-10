<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moving Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the moving service module including pricing,
    | service options, and default values.
    |
    */

    'base_price' => env('MOVING_BASE_PRICE', 500),

    'price_per_m3' => env('MOVING_PRICE_PER_M3', 50),

    'price_per_km' => env('MOVING_PRICE_PER_KM', 10),

    'free_distance_km' => env('MOVING_FREE_DISTANCE_KM', 5),

    'floor_surcharge' => env('MOVING_FLOOR_SURCHARGE', 50),

    'service_prices' => [
        'assembly' => env('MOVING_SERVICE_ASSEMBLY', 100),
        'disassembly' => env('MOVING_SERVICE_DISASSEMBLY', 80),
        'packaging' => env('MOVING_SERVICE_PACKAGING', 50),
        'wrapping' => env('MOVING_SERVICE_WRAPPING', 30),
        'takelage' => env('MOVING_SERVICE_TAKELAGE', 150),
        'electronics' => env('MOVING_SERVICE_ELECTRONICS', 120),
    ],

    'weather' => [
        'default_coefficient' => env('MOVING_WEATHER_COEFFICIENT', 1.0),
    ],

    'package_types' => [
        'economy' => [
            'name' => 'Економ',
            'multiplier' => 0.9,
        ],
        'standard' => [
            'name' => 'Стандарт',
            'multiplier' => 1.0,
        ],
        'premium' => [
            'name' => 'Преміум',
            'multiplier' => 1.2,
        ],
    ],
];
