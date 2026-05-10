<?php

use App\Http\Controllers\Api\AgencyAgents\AgentRunController;
use App\Http\Controllers\Api\AgencyAgents\AgentStepController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:agency-agents'])->prefix('agency-agents')->group(function () {
    Route::get('/runs', [AgentRunController::class, 'index']);
    Route::post('/runs', [AgentRunController::class, 'store']);
    Route::get('/runs/{run}', [AgentRunController::class, 'show']);
    Route::get('/runs/{run}/workspace', [AgentRunController::class, 'workspace']);
    Route::get('/runs/{run}/threads', [AgentRunController::class, 'threads']);
    Route::get('/runs/{run}/events', [AgentRunController::class, 'events']);
    Route::get('/runs/{run}/artifacts', [AgentRunController::class, 'artifacts']);
    Route::patch('/runs/{run}/status', [AgentRunController::class, 'updateStatus']);

    Route::get('/steps', [AgentStepController::class, 'index']);
    Route::get('/steps/{step}', [AgentStepController::class, 'show']);
    Route::patch('/steps/{step}/status', [AgentStepController::class, 'updateStatus']);
    Route::post('/steps/{step}/heartbeat', [AgentStepController::class, 'heartbeat']);
});
