<?php

use App\Http\Controllers\Api\Ops\AssignmentController;
use App\Http\Controllers\Api\Ops\CandidateCompareController;
use App\Http\Controllers\Api\Ops\DispatchWorkbenchController;
use App\Http\Controllers\Api\Ops\ExceptionDrawerController;
use App\Http\Controllers\Api\Ops\ExceptionController;
use App\Http\Controllers\Api\Ops\ExecutorDrawerController;
use App\Http\Controllers\Api\Ops\ExecutorController;
use App\Http\Controllers\Api\Ops\JobDrawerController;
use App\Http\Controllers\Api\Ops\LiveFeedController;
use App\Http\Controllers\Api\Ops\MapController;
use App\Http\Controllers\Api\Ops\ReplanRecommendationsController;
use App\Http\Controllers\Api\Ops\RoutingShadowMetricsController;
use App\Http\Controllers\Api\Ops\SavedOpsFilterController;
use App\Http\Controllers\Api\Ops\ServiceJobController;
use App\Http\Controllers\Api\Ops\WorkbenchBulkActionController;
use App\Http\Middleware\RequireWorkbenchIdempotencyKey;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('ops')->group(function () {
    Route::get('/jobs', [ServiceJobController::class, 'index']);
    Route::get('/jobs/{job}', [ServiceJobController::class, 'show']);
    Route::post('/jobs/{job}/dispatch', [ServiceJobController::class, 'dispatch']);
    Route::get('/jobs/{job}/drawer', [JobDrawerController::class, 'show']);
    Route::get('/jobs/{job}/candidate-compare', [CandidateCompareController::class, 'show']);
    Route::post('/jobs/{job}/manual-dispatch', [DispatchWorkbenchController::class, 'manualDispatch'])
        ->middleware([RequireWorkbenchIdempotencyKey::class]);
    Route::post('/jobs/{job}/manual-reassign', [DispatchWorkbenchController::class, 'manualReassign'])
        ->middleware([RequireWorkbenchIdempotencyKey::class]);
    Route::get('/jobs/{job}/timeline', [ServiceJobController::class, 'timeline']);
    Route::get('/jobs/{job}/exceptions', [ServiceJobController::class, 'exceptions']);

    Route::get('/executors', [ExecutorController::class, 'index']);
    Route::get('/executors/{executor}', [ExecutorController::class, 'show']);
    Route::get('/executors/{executor}/drawer', [ExecutorDrawerController::class, 'show']);
    Route::patch('/executors/{executor}/availability', [ExecutorController::class, 'availability']);
    Route::post('/executors/{executor}/location-pings', [ExecutorController::class, 'locationPing']);

    Route::post('/assignments/{assignment}/accept', [AssignmentController::class, 'accept']);
    Route::post('/assignments/{assignment}/reject', [AssignmentController::class, 'reject']);
    Route::post('/assignments/{assignment}/start-travel', [AssignmentController::class, 'startTravel']);
    Route::post('/assignments/{assignment}/arrive', [AssignmentController::class, 'arrive']);
    Route::post('/assignments/{assignment}/start-work', [AssignmentController::class, 'startWork']);
    Route::post('/assignments/{assignment}/complete', [AssignmentController::class, 'complete']);

    Route::get('/map/live', [MapController::class, 'live']);
    Route::get('/workbench/triage', [WorkbenchBulkActionController::class, 'triage']);
    Route::get('/workbench/replan-recommendations', [ReplanRecommendationsController::class, 'index']);
    Route::get('/workbench/routing-shadow-metrics', [RoutingShadowMetricsController::class, 'index']);
    Route::get('/workbench/routing-provider-health', [RoutingShadowMetricsController::class, 'health']);
    Route::post('/workbench/bulk-action', [WorkbenchBulkActionController::class, 'apply']);
    Route::get('/workbench/saved-filters', [SavedOpsFilterController::class, 'index']);
    Route::post('/workbench/saved-filters', [SavedOpsFilterController::class, 'store']);
    Route::delete('/workbench/saved-filters/{filter}', [SavedOpsFilterController::class, 'destroy']);
    Route::get('/live-feed', LiveFeedController::class);

    Route::get('/exceptions', [ExceptionController::class, 'index']);
    Route::post('/exceptions/{exception}/ack', [ExceptionController::class, 'ack']);
    Route::post('/exceptions/{exception}/resolve', [ExceptionController::class, 'resolve']);
    Route::get('/exceptions/{exception}/drawer', [ExceptionDrawerController::class, 'show']);
    Route::post('/exceptions/{exception}/acknowledge', [DispatchWorkbenchController::class, 'acknowledgeException'])
        ->middleware([RequireWorkbenchIdempotencyKey::class]);
    Route::post('/exceptions/{exception}/resolve-workbench', [DispatchWorkbenchController::class, 'resolveException'])
        ->middleware([RequireWorkbenchIdempotencyKey::class]);
});
