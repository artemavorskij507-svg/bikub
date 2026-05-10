<?php

use App\Enums\DeliveryType;

return [
    /*
    |--------------------------------------------------------------------------
    | Delivery Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different delivery types: grocery, bulky, food
    |
    */

    'types' => [
        DeliveryType::GROCERY->value => [
            'base_time' => 15, // minutes
            'time_per_km' => 2, // minutes per km
            'delivery_fee' => 49, // NOK (Narvik base)
            'base_rate' => 0, // Products have their own prices
        ],

        DeliveryType::BULKY->value => [
            'base_time' => 30, // minutes
            'time_per_km' => 3, // minutes per km
            'base_rate' => 200, // NOK
            'rate_per_m3' => 50, // NOK per cubic meter
            'service_prices' => [
                'assembly' => 100,
                'disassembly' => 80,
                'packaging' => 50,
                'wrapping' => 30,
            ],
        ],

        DeliveryType::FOOD->value => [
            'base_time' => 20, // minutes
            'time_per_km' => 2.5, // minutes per km
            'delivery_fee' => 40, // NOK
            'base_rate' => 0, // Restaurant items have their own prices
        ],
    ],

    'weather' => [
        'default_coefficient' => 1.0,
        'snow_coefficient' => 1.3,
        'rain_coefficient' => 1.1,
        'ice_coefficient' => 1.5,
    ],

    'auto_assign_courier' => env('DELIVERY_AUTO_ASSIGN_COURIER', true),
];
