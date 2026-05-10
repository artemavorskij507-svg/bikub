<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes (named)
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/catalog', [PublicController::class, 'catalog'])->name('public.catalog');
Route::get('/category/{slug}', [PublicController::class, 'category'])->name('public.category');
Route::get('/service/{slug}', [PublicController::class, 'service'])->name('public.service');

// Back-compat route names
Route::get('/catalog/{categoryCode}', [PublicController::class, 'categoryServices'])->name('catalog.category');
Route::get('/order/{serviceCode}', [PublicController::class, 'orderForm'])->name('order.form');

// Category-specific routes
Route::get('/care', [PublicController::class, 'care'])->name('care');
Route::get('/eco', [PublicController::class, 'eco'])->name('eco');
Route::get('/market', [PublicController::class, 'market'])->name('market');
Route::get('/tow', [PublicController::class, 'tow'])->name('tow');
Route::get('/rent', [PublicController::class, 'rent'])->name('rent');
Route::get('/shuttle', [PublicController::class, 'shuttle'])->name('shuttle');
Route::get('/master', [PublicController::class, 'master'])->name('master');
Route::get('/food', [PublicController::class, 'food'])->name('food');

// API info route (for backward compatibility)
Route::get('/api-info', function () {
    return response()->json([
        'message' => 'ROMA ∞ - GLF Bike Care API',
        'status' => 'ok',
        'api_endpoints' => [
            '/api/v1/health',
            '/api/v1/service-types',
            '/api/v1/service-types/{slug}',
            '/api/v1/service-types/category/{category}',
        ],
        'admin_panel' => '/admin',
        'login' => '/login',
    ]);
});

// Lightweight health check without DB
Route::get('/healthz', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Authentication routes for Filament
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
Route::post('/login', fn () => redirect('/admin/login'));
Route::get('/logout', function () {
    auth()->logout();

    return redirect('/admin/login');
})->name('logout');

// Registration page
Route::get('/register', function () {
    return view('filament.pages.register');
})->name('register');

// Admin panel routes are handled by Filament
// Alias for filament.pages.analytics route (Filament auto-registers /admin/analytics)
Route::get('/__alias/filament.pages.analytics', function () {
    return redirect('/admin/analytics');
})->name('filament.pages.analytics');

// Fallback: redirect all unknown routes to catalog
Route::fallback(function () {
    return redirect('/catalog');
});
