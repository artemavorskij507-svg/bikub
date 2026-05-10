<?php

use App\Http\Controllers\Api\AgencyHubController;
use App\Http\Middleware\EnsureAgencySharedApiKey;
use Illuminate\Support\Facades\Route;

/*
| Добавь в routes/api.php: require __DIR__.'/agency_hub.php';
| Итоговые URL: /api/agency-hub/...
*/

Route::get('agency-hub/health', static function () {
    return response()->json([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
});

Route::middleware(['api', EnsureAgencySharedApiKey::class])->group(function (): void {
    Route::get('agency-hub/me', [AgencyHubController::class, 'me']);
    Route::get('agency-hub/agents', [AgencyHubController::class, 'agents']);
    Route::get('agency-hub/communications', [AgencyHubController::class, 'communications']);
    Route::post('agency-hub/communications', [AgencyHubController::class, 'storeCommunication']);
});
