<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament Path
    |--------------------------------------------------------------------------
    |
    | The default is `/admin` but you can change it to whatever works best and
    | doesn't conflict with the routing in your application.
    |
    */

    'path' => env('FILAMENT_PATH', 'admin'),

    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'pages' => [
            'login' => \Filament\Http\Livewire\Auth\Login::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Core Path
    |--------------------------------------------------------------------------
    |
    | This is the path which Filament will use to load your resources, actions
    | pages, widgets, and other features.
    |
    */

    'core_path' => env('FILAMENT_CORE_PATH', 'app/Filament'),

    /*
    |--------------------------------------------------------------------------
    | Resources Path
    |--------------------------------------------------------------------------
    |
    | This is where the Resources are registered.
    |
    */

    'resources_path' => env('FILAMENT_RESOURCES_PATH', app_path('Filament/Resources')),

    /*
    |--------------------------------------------------------------------------
    | Pages Path
    |--------------------------------------------------------------------------
    |
    | This is where the Pages are registered.
    |
    */

    'pages_path' => env('FILAMENT_PAGES_PATH', app_path('Filament/Pages')),

    /*
    |--------------------------------------------------------------------------
    | Widgets Path
    |--------------------------------------------------------------------------
    |
    | This is where the Widgets are registered.
    |
    */

    'widgets_path' => env('FILAMENT_WIDGETS_PATH', app_path('Filament/Widgets')),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Define the middleware that should be applied to the Filament pages.
    |
    */

    'middleware' => [
        'auth' => [
            \Illuminate\Auth\Middleware\Authenticate::class,
        ],
        'base' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | This array contains the pages which should be registered automatically
    | when Filament starts.
    |
    */

    'pages' => [
        'login' => \Filament\Http\Livewire\Auth\Login::class,
        \App\Filament\Pages\Dashboard::class,
        // Roadside pages регистрируются явно в FilamentServiceProvider
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | This array contains the resources which should be registered automatically
    | when Filament starts.
    |
    */

    'resources' => [
        // Roadside resources регистрируются явно в FilamentServiceProvider
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    |
    | Enable dark mode for all pages.
    |
    */

    'dark_mode' => env('FILAMENT_DARK_MODE', false),

];
