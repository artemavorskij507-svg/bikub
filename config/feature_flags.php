<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Управление функциями через feature flags для безопасного включения/выключения
    | без изменения кода.
    |
    */

    'auto_assign' => env('FF_AUTO_ASSIGN', false),
    'strict_payment_gate' => env('FF_STRICT_PAYMENT', true),
    'homepage-3d-cards' => env('FF_HOMEPAGE_3D_CARDS', true),

    /*
    |--------------------------------------------------------------------------
    | Winter Protocol Settings
    |--------------------------------------------------------------------------
    */
    'winter_protocol' => [
        'enabled' => env('FF_WINTER_PROTOCOL', false),
        'eta_multiplier' => env('WINTER_ETA_MULTIPLIER', 1.25),
        'min_overbooking_pct' => env('WINTER_MIN_OVERBOOKING', 0),
        'restricted_vehicle_types' => ['bike', 'foot'],
        'additional_equipment' => ['chains', 'thermal_bag'],
    ],

    'enable_dynamic_pricing' => env('FF_ENABLE_DYNAMIC_PRICING', true),
];
