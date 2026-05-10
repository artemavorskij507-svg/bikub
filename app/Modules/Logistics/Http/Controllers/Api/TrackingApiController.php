<?php

namespace App\Modules\Logistics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Events\CourierLocationUpdated;
use App\Modules\Logistics\Events\ShipmentLifecycleChanged;
use App\Modules\Logistics\Http\Requests\UpdateTrackingRequest;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Models\TrackingEvent;
use Illuminate\Http\JsonResponse;

class TrackingApiController extends Controller
{
    public function showByTracking(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::query()->forTracking($trackingNumber)->firstOrFail();

        return response()->json(['data' => $shipment->load('trackingEvents')]);
    }

    public function update(UpdateTrackingRequest $request, Shipment $shipment): JsonResponse
    {
        $validated = $request->validated();

        $event = TrackingEvent::create(array_merge($request->safe()->except([
            'status',
            'message',
            'happened_at',
            'source',
            'metadata',
        ]), [
            'shipment_id' => $shipment->id,
            'event_status' => $validated['status'],
            'event_time' => $validated['happened_at'] ?? now(),
            'source_system' => $validated['source'] ?? 'api',
            'payload' => array_filter([
                'message' => $validated['message'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
            ]),
        ]));

        $shipment->update(['status' => $validated['status']]);

        ShipmentLifecycleChanged::dispatch($shipment, 'shipment.tracking.updated', [
            'tracking_event_id' => $event->id,
            'status' => $validated['status'],
            'event_type' => $validated['event_type'],
        ]);

        if (isset($validated['latitude'], $validated['longitude'])) {
            CourierLocationUpdated::dispatch($shipment, [
                'personnel_id' => $shipment->assigned_personnel_id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'tracking_event_id' => $event->id,
            ]);
        }

        return response()->json(['data' => $event], 201);
    }
}

