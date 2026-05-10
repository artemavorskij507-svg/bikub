<?php

use App\Http\Controllers\Admin\RoadsideDispatchController;
use Illuminate\Support\Facades\Route;

// Admin panel routes - без SetCurrentZone middleware
// ВАЖНО: НЕ реєструємо маршрут /admin тут - Filament сам обробляє /admin через свою панель
// Якщо зареєструвати /admin тут, він перехопить запити до Filament панелі

// Authentication routes for Filament
// /login редиректить на /admin/login (Filament login)
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
Route::post('/login', fn () => redirect('/admin/login'));

Route::get('/logout', function () {
    auth()->logout();

    return redirect('/admin/login');
})->name('admin.logout');

// Legacy Ops slugs -> canonical pages/resources.
Route::redirect('/admin/operations-core-board', '/admin/operations-core', 301);
Route::redirect('/admin/service-jobs-board', '/admin/service-jobs', 301);
Route::redirect('/admin/live-ops-map', '/admin/live-operations-map', 301);
Route::redirect('/admin/operation-exceptions-list', '/admin/operation-exceptions', 301);
Route::redirect('/admin/exception-sla-center', '/admin/operation-exceptions', 301)->name('admin.exception-sla-center.legacy');

// Roadside dispatch routes
Route::middleware(['web', 'auth'])
    ->prefix('admin/roadside')
    ->name('admin.roadside.')
    ->group(function () {
        Route::post('assign-helper', [RoadsideDispatchController::class, 'assignHelper'])
            ->name('assign-helper');

        Route::post('assign-partner', [RoadsideDispatchController::class, 'assignPartner'])
            ->name('assign-partner');
    });
