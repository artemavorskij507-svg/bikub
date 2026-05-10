<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Queries\CandidateCompareQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateCompareController extends Controller
{
    use ResolvesOpsScope;

    public function show(ServiceJob $job, Request $request, CandidateCompareQuery $query): JsonResponse
    {
        $this->authorize('view', $job);

        $organizationId = $this->resolveOrganizationScope($job->organization_id);
        $left = $request->integer('left_executor_id') ?: null;
        $right = $request->integer('right_executor_id') ?: null;

        return response()->json(
            $query->execute($organizationId, $job->id, $left, $right)
        );
    }
}
