<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Routing\Actions\BuildRoutingBaselineReportAction;
use App\Domain\Routing\Actions\CheckRoutingProviderHealthAction;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutingShadowMetricsController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request, BuildRoutingBaselineReportAction $buildRoutingBaselineReportAction): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $organizationId = $this->resolveOrganizationScope();
        $days = max(1, min(30, $request->integer('days') ?: 3));

        return response()->json(
            $buildRoutingBaselineReportAction->execute($organizationId, $days)
        );
    }

    public function health(Request $request, CheckRoutingProviderHealthAction $checkRoutingProviderHealthAction): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        return response()->json($checkRoutingProviderHealthAction->execute());
    }
}

