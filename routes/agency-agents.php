<?php

use App\Http\Middleware\IdentifyTenantFromApiKey;
use App\Modules\AgencyAgents\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agency Agents API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('agency-agents')->middleware(['api', IdentifyTenantFromApiKey::class])->group(function () {
    Route::get('/overview', [AgentController::class, 'systemOverview']);
    Route::get('/health', [AgentController::class, 'healthCheck']);
    Route::get('/report', [AgentController::class, 'report']);
    Route::get('/categories/stats', [AgentController::class, 'categoryStats']);
    Route::get('/top-performers', [AgentController::class, 'topPerformers']);
    Route::get('/recent-activities', [AgentController::class, 'recentActivities']);
    Route::get('/heatmap', [AgentController::class, 'heatmapData']);

    Route::get('/zones', [AgentController::class, 'zones']);
    Route::get('/zones/{zoneName}', [AgentController::class, 'zoneDetails']);
    Route::get('/zones/stats', [AgentController::class, 'zoneStats']);

    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{agent}', [AgentController::class, 'show']);
    Route::put('/agents/{agent}/position', [AgentController::class, 'updatePosition']);
    Route::put('/agents/{agent}/status', [AgentController::class, 'updateStatus']);
    Route::post('/agents/{agent}/move', [AgentController::class, 'moveToZone']);

    Route::get('/agents/{agent}/tasks', [AgentController::class, 'tasks']);
    Route::post('/agents/{agent}/tasks', [AgentController::class, 'createTask']);

    Route::get('/agents/{agent}/communications', [AgentController::class, 'communications']);
    Route::post('/agents/{agent}/messages', [AgentController::class, 'sendMessage']);

    Route::get('/agents/{agent}/performance', [AgentController::class, 'performance']);
    Route::get('/agents/{agent}/metrics', [AgentController::class, 'metrics']);
    Route::get('/agents/{agent}/activities', [AgentController::class, 'activities']);
});
