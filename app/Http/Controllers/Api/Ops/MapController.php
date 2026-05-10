<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Ops\Queries\LiveOperationsMapQuery;
use App\Domain\Operations\Models\ServiceJob;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    use ResolvesOpsScope;

    public function live(Request $request, LiveOperationsMapQuery $query): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);
        $organizationId = $this->resolveOrganizationScope();

        $state = $query->execute([
            'domain' => $request->string('domain')->toString() ?: null,
            'zone' => $request->string('zone')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'at_risk_only' => (bool) $request->boolean('at_risk_only'),
            'exceptions_only' => (bool) $request->boolean('exceptions_only'),
            'executors_only' => (bool) $request->boolean('executors_only'),
        ], $organizationId);

        return response()->json([
            'jobs' => $state['jobs'],
            'executors' => $state['executors'],
            'exceptions' => $state['exceptions'],
        ]);
    }
}
