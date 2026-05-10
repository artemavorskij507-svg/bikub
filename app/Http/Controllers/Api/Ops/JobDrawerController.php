<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Queries\LiveJobDrawerQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;

class JobDrawerController extends Controller
{
    use ResolvesOpsScope;

    public function show(ServiceJob $job, LiveJobDrawerQuery $liveJobDrawerQuery): JsonResponse
    {
        $this->authorize('view', $job);

        $organizationId = $this->resolveOrganizationScope($job->organization_id);

        return response()->json(
            $liveJobDrawerQuery->execute($organizationId, $job->id)
        );
    }
}
