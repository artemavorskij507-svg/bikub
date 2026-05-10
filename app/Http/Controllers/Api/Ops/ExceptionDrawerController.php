<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Ops\Queries\ExceptionDrawerQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;

class ExceptionDrawerController extends Controller
{
    use ResolvesOpsScope;

    public function show(OperationException $exception, ExceptionDrawerQuery $exceptionDrawerQuery): JsonResponse
    {
        $this->authorize('view', $exception);

        $organizationId = $this->resolveOrganizationScope($exception->organization_id);

        return response()->json(
            $exceptionDrawerQuery->execute($organizationId, $exception->id)
        );
    }
}
