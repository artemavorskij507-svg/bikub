<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Tracking\Actions\CacheExecutorLocationAction;
use App\Domain\Operations\Models\Executor;
use App\Domain\Tracking\Models\ExecutorLocation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Http\Requests\Ops\StoreExecutorLocationPingRequest;
use App\Http\Requests\Ops\UpdateExecutorAvailabilityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ExecutorController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Executor::class);
        $organizationId = $this->resolveOrganizationScope();

        $executors = Executor::query()
            ->where('organization_id', $organizationId)
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($executors);
    }

    public function show(Executor $executor): JsonResponse
    {
        $this->authorize('view', $executor);

        $executor->load(['assignments', 'locations']);

        return response()->json($executor);
    }

    public function availability(UpdateExecutorAvailabilityRequest $request, Executor $executor): JsonResponse
    {
        $executor->update($request->validated());

        Redis::setex("executor:{$executor->id}:status", 300, $executor->status);

        return response()->json([
            'message' => 'Executor availability updated.',
            'executor' => $executor->fresh(),
        ]);
    }

    public function locationPing(
        StoreExecutorLocationPingRequest $request,
        Executor $executor,
        CacheExecutorLocationAction $cacheExecutorLocationAction
    ): JsonResponse
    {
        $data = $request->validated();

        $location = ExecutorLocation::create([
            'organization_id' => $executor->organization_id,
            'tenant_id' => $executor->tenant_id,
            'executor_id' => $executor->id,
            'assignment_id' => $data['assignment_id'] ?? null,
            'service_job_id' => $data['service_job_id'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'heading' => $data['heading'] ?? null,
            'speed' => $data['speed'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        $cacheExecutorLocationAction->execute($location);

        return response()->json([
            'message' => 'Location ping stored.',
            'location_id' => $location->id,
        ], 201);
    }
}
