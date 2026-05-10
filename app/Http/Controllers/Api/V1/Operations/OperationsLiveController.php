<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Events\Operations\ExecutorLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Operations\Executor;
use App\Models\Operations\ExecutorLocation;
use App\Services\Operations\LiveOpsStateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsLiveController extends Controller
{
    public function __construct(private readonly LiveOpsStateService $liveOpsStateService) {}

    public function state(Request $request)
    {
        $filters = $request->validate([
            'organization_id' => 'nullable|string',
            'service_domain' => 'nullable|in:delivery,handyman',
            'status' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->liveOpsStateService->getState($filters),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function updateLocation(Request $request, int $executorId)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0',
            'heading' => 'nullable|integer|min:0|max:360',
        ]);

        $executor = Executor::find($executorId);
        if (! $executor) {
            throw ValidationException::withMessages(['executor' => 'Executor not found']);
        }

        ExecutorLocation::create([
            'executor_id' => $executor->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'speed_kmh' => $data['speed_kmh'] ?? null,
            'heading' => $data['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        $executor->update(['last_seen_at' => now()]);

        $this->liveOpsStateService->updateExecutorHotLocation($executor->id, (float) $data['lat'], (float) $data['lng'], [
            'speed_kmh' => $data['speed_kmh'] ?? null,
            'heading' => $data['heading'] ?? null,
        ]);

        event(new ExecutorLocationUpdated($executor->fresh(), (float) $data['lat'], (float) $data['lng'], $data));

        return response()->json([
            'success' => true,
            'data' => [
                'executor_id' => $executor->id,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}

