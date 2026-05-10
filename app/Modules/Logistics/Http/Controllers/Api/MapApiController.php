<?php

namespace App\Modules\Logistics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Events\CourierLocationUpdated;
use App\Modules\Logistics\Models\DeliveryPersonnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapApiController extends Controller
{
    public function personnelPositions(): JsonResponse
    {
        $positions = DeliveryPersonnel::query()
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->get([
                'id',
                'user_id',
                'role',
                'status',
                'last_latitude',
                'last_longitude',
                'last_location_at',
                'vehicle_type',
                'home_warehouse_id',
            ])
            ->map(function (DeliveryPersonnel $personnel) {
                return [
                    'id' => $personnel->id,
                    'user_id' => $personnel->user_id,
                    'role' => $personnel->role,
                    'status' => $personnel->status,
                    'current_latitude' => $personnel->last_latitude,
                    'current_longitude' => $personnel->last_longitude,
                    'last_location_at' => $personnel->last_location_at,
                    'vehicle_type' => $personnel->vehicle_type,
                    'home_warehouse_id' => $personnel->home_warehouse_id,
                ];
            })
            ->values();

        return response()->json(['data' => $positions]);
    }

    public function updatePersonnelPosition(Request $request, DeliveryPersonnel $personnel): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $personnel->update([
            'last_latitude' => $validated['latitude'],
            'last_longitude' => $validated['longitude'],
            'last_location_at' => $validated['recorded_at'] ?? now(),
        ]);

        CourierLocationUpdated::dispatch(null, [
            'personnel_id' => $personnel->id,
            'latitude' => $personnel->last_latitude,
            'longitude' => $personnel->last_longitude,
            'recorded_at' => $personnel->last_location_at,
        ]);

        return response()->json([
            'data' => [
                'id' => $personnel->id,
                'latitude' => $personnel->last_latitude,
                'longitude' => $personnel->last_longitude,
                'recorded_at' => $personnel->last_location_at,
            ],
        ]);
    }
}

