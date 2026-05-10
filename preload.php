<?php

/**
 * OPcache Preload Script
 *
 * This file preloads frequently used classes to improve performance.
 * Configure in php.ini: opcache.preload=/var/www/glfbikube/preload.php
 */

// Core Laravel classes
opcache_compile_file(__DIR__.'/vendor/laravel/framework/src/Illuminate/Foundation/Application.php');
opcache_compile_file(__DIR__.'/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php');
opcache_compile_file(__DIR__.'/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php');
opcache_compile_file(__DIR__.'/vendor/laravel/framework/src/Illuminate/Http/Request.php');
opcache_compile_file(__DIR__.'/vendor/laravel/framework/src/Illuminate/Http/Response.php');

// Application models
$models = [
    'App/Models/Task.php',
    'App/Models/Order.php',
    'App/Models/User.php',
    'App/Models/Employee.php',
    'App/Models/ScheduleSlot.php',
    'App/Models/GeoZone.php',
    'App/Models/TrafficIncident.php',
    'App/Models/TravelTime.php',
];

foreach ($models as $model) {
    $path = __DIR__.'/'.$model;
    if (file_exists($path)) {
        opcache_compile_file($path);
    }
}

// Services
$services = [
    'App/Services/WebhookNotifier.php',
    'App/Services/TaskGenerator.php',
    'App/Services/VegvesenCkanClient.php',
];

foreach ($services as $service) {
    $path = __DIR__.'/'.$service;
    if (file_exists($path)) {
        opcache_compile_file($path);
    }
}
