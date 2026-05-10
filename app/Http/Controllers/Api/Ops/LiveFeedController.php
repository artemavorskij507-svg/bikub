<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Ops\Queries\LiveOperationsMapQuery;
use App\Domain\Ops\Queries\OpsSummaryQuery;
use App\Domain\Operations\Models\ServiceJob;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveFeedController extends Controller
{
    use ResolvesOpsScope;

    public function __invoke(
        Request $request,
        LiveOperationsMapQuery $liveOperationsMapQuery,
        OpsSummaryQuery $opsSummaryQuery,
    ): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);
        $organizationId = $this->resolveOrganizationScope();

        $filters = [
            'domain' => $request->string('domain')->toString() ?: null,
            'zone' => $request->string('zone')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'at_risk_only' => (bool) $request->boolean('at_risk_only'),
            'exceptions_only' => (bool) $request->boolean('exceptions_only'),
            'executors_only' => (bool) $request->boolean('executors_only'),
        ];

        $state = $liveOperationsMapQuery->execute($filters, $organizationId);
        $summary = $opsSummaryQuery->execute($filters, $organizationId)['kpi'];

        return response()->json([
            'summary' => $summary,
            'jobs' => $state['jobs'],
            'executors' => $state['executors'],
            'exceptions' => $state['exceptions'],
            'filters' => $filters,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
