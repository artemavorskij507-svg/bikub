<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Queries\ReplanRecommendationsQuery;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplanRecommendationsController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request, ReplanRecommendationsQuery $query): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $organizationId = $this->resolveOrganizationScope();
        $serviceJobId = $request->integer('service_job_id') ?: null;
        $limit = $request->integer('limit') ?: 50;

        return response()->json([
            'items' => $query->execute($organizationId, $serviceJobId, $limit),
        ]);
    }
}

