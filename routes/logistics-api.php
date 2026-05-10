<?php

use App\Modules\Logistics\Http\Controllers\Api\CustomerPortalController;
use App\Modules\Logistics\Http\Controllers\Api\MapApiController;
use App\Modules\Logistics\Http\Controllers\Api\ShipmentApiController;
use App\Modules\Logistics\Http\Controllers\Api\TrackingApiController;
use App\Modules\Logistics\Http\Controllers\Api\WorkerPortalController;
use App\Modules\Logistics\Http\Middleware\EnsureDeliveryPersonnel;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/logistics')->name('api.v1.logistics.')->group(function () {
    Route::get('/tracking/{trackingNumber}', [TrackingApiController::class, 'showByTracking'])->name('tracking.show');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/shipments', [ShipmentApiController::class, 'index'])->name('shipments.index');
        Route::post('/shipments', [ShipmentApiController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{shipment}', [ShipmentApiController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/{shipment}/tracking-events', [TrackingApiController::class, 'update'])->name('tracking-events.store');
        Route::get('/map/personnel-positions', [MapApiController::class, 'personnelPositions'])->name('map.personnel');
        Route::put('/map/personnel/{personnel}/position', [MapApiController::class, 'updatePersonnelPosition'])->name('map.personnel.update');
        Route::get('/customer/shipments', [CustomerPortalController::class, 'shipments'])->name('customer.shipments');
        Route::get('/worker/shipments', [WorkerPortalController::class, 'assigned'])
            ->middleware(EnsureDeliveryPersonnel::class)
            ->name('worker.shipments');
    });
});

