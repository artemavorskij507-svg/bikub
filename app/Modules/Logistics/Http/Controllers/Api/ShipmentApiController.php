<?php

namespace App\Modules\Logistics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Events\ShipmentLifecycleChanged;
use App\Modules\Logistics\Http\Requests\CreateShipmentRequest;
use App\Modules\Logistics\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ShipmentApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Shipment::query()->latest('id')->paginate(20));
    }

    public function store(CreateShipmentRequest $request): JsonResponse
    {
        $shipment = Shipment::create(array_merge($request->safe()->except([
            'customer_address_id',
            'scheduled_pickup_at',
            'scheduled_delivery_at',
        ]), [
            'shipment_number' => 'BK' . strtoupper(Str::random(12)),
            'status' => 'created',
            'currency' => 'NOK',
        ]));

        ShipmentLifecycleChanged::dispatch($shipment, 'shipment.created', [
            'source' => 'api',
            'status' => $shipment->status,
        ]);

        return response()->json(['data' => $shipment], 201);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return response()->json(['data' => $shipment->load(['parcels', 'trackingEvents'])]);
    }
}

