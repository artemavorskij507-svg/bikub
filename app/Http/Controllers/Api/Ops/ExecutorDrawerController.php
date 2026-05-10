<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\Executor;
use App\Domain\Ops\Queries\ExecutorDrawerQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;

class ExecutorDrawerController extends Controller
{
    use ResolvesOpsScope;

    public function show(Executor $executor, ExecutorDrawerQuery $executorDrawerQuery): JsonResponse
    {
        $this->authorize('view', $executor);

        $organizationId = $this->resolveOrganizationScope($executor->organization_id);

        return response()->json(
            $executorDrawerQuery->execute($organizationId, $executor->id)
        );
    }
}
