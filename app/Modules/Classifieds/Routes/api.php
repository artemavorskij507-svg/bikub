<?php

use App\Modules\Classifieds\Controllers\AdPromotionController;
use App\Modules\Classifieds\Controllers\AIGenerationController;
use App\Modules\Classifieds\Controllers\ClassifiedAdController;
use App\Modules\Classifieds\Controllers\ClassifiedMapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Classifieds Module API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/classifieds')->group(function () {
    // Public
    Route::get('/', [ClassifiedAdController::class, 'index']);
    Route::get('/map-data', [ClassifiedMapController::class, 'index']);
    Route::get('/geocode', [ClassifiedMapController::class, 'geocode']);
    Route::get('/{ad}', [ClassifiedAdController::class, 'show']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ClassifiedAdController::class, 'store']);
        Route::put('/{ad}', [ClassifiedAdController::class, 'update']);
        Route::delete('/{ad}', [ClassifiedAdController::class, 'destroy']);

        // Promotions
        Route::post('/{ad}/promotion/calc', [AdPromotionController::class, 'calculatePrice']);
        Route::post('/{ad}/promotion/apply', [AdPromotionController::class, 'purchase']);

        // AI helpers (generation)
        Route::post('/generate-description', [AIGenerationController::class, 'generateDescription']);

        // Alerts
        Route::post('/alerts', [\App\Modules\Classifieds\Controllers\AdAlertController::class, 'store']);
    });
});

// Health Check for Kubernetes/Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'services' => [
            'database' => \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'connected' : 'failed',
            'redis' => \Illuminate\Support\Facades\Redis::connection() ? 'connected' : 'failed',
        ],
    ]);
});
