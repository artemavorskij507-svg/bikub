<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Exceptions\Actions\AcknowledgeOperationExceptionAction;
use App\Domain\Exceptions\Actions\ResolveOperationExceptionAction;
use App\Domain\Exceptions\Enums\OperationExceptionStatus;
use App\Domain\Exceptions\Models\OperationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Http\Requests\Ops\ResolveOperationExceptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OperationException::class);
        $organizationId = $this->resolveOrganizationScope();

        $exceptions = OperationException::query()
            ->where('organization_id', $organizationId)
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('severity')->toString(), fn ($q, $severity) => $q->where('severity', $severity))
            ->latest('detected_at')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($exceptions);
    }

    public function ack(
        OperationException $exception,
        AcknowledgeOperationExceptionAction $acknowledgeOperationExceptionAction,
    ): JsonResponse
    {
        $this->authorize('update', $exception);

        if ($exception->status === OperationExceptionStatus::RESOLVED->value) {
            return response()->json(['message' => 'Exception already resolved.']);
        }

        $acknowledgeOperationExceptionAction->execute($exception, (int) auth()->id());

        return response()->json(['message' => 'Exception acknowledged.']);
    }

    public function resolve(
        ResolveOperationExceptionRequest $request,
        OperationException $exception,
        ResolveOperationExceptionAction $resolveOperationExceptionAction,
    ): JsonResponse
    {
        $this->authorize('resolve', $exception);

        $resolveOperationExceptionAction->execute(
            exception: $exception,
            userId: (int) auth()->id(),
            resolutionCode: (string) $request->validated('resolution_code'),
            resolutionNotes: $request->validated('resolution_notes'),
            rootCause: $request->validated('root_cause'),
        );

        return response()->json(['message' => 'Exception resolved.']);
    }
}
