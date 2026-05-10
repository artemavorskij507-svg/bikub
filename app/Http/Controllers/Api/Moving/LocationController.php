<?php

namespace App\Http\Controllers\Api\Moving;

use App\Http\Controllers\Controller;
use App\Models\Moving\MovingOrder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    public function show(MovingOrder $movingOrder)
    {
        $location = data_get($movingOrder->metadata, 'current_location', []);

        return response()->json([
            'data' => $location,
        ]);
    }

    public function update(Request $request, MovingOrder $movingOrder)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'captured_at' => 'nullable|date',
        ]);

        $metadata = $movingOrder->metadata ?? [];
        $metadata['current_location'] = [
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'accuracy' => $data['accuracy'] ?? null,
            'captured_at' => $data['captured_at'] ?? now()->toIso8601String(),
        ];

        $movingOrder->update([
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => 'Локацію оновлено',
            'data' => $metadata['current_location'],
        ], Response::HTTP_ACCEPTED);
    }
}
