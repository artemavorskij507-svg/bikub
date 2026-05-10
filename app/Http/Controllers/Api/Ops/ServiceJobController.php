<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Http\Requests\Ops\DispatchServiceJobRequest;
use App\Jobs\CalculateDispatchCandidatesJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceJobController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);
        $organizationId = $this->resolveOrganizationScope();

        $query = ServiceJob::query()
            ->where('organization_id', $organizationId)
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('service_domain')->toString(), fn ($q, $domain) => $q->where('service_domain', $domain))
            ->when($request->string('priority')->toString(), fn ($q, $priority) => $q->where('priority', $priority))
            ->latest();

        $jobs = $query->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($jobs);
    }

    public function show(ServiceJob $job): JsonResponse
    {
        $this->authorize('view', $job);

        $job->load([
            'currentAssignment.executor',
            'assignments.executor',
            'slaTimer',
            'exceptions',
            'timeline',
        ]);

        return response()->json($job);
    }

    public function dispatch(DispatchServiceJobRequest $request, ServiceJob $job): JsonResponse
    {
        $mode = (string) $request->validated('mode', 'auto');
        $executorId = $request->validated('executor_id');

        if ($mode === 'manual' && $executorId) {
            // Manual assignment action can be implemented here in the next phase.
        } else {
            CalculateDispatchCandidatesJob::dispatch($job->id)->onQueue('dispatch-default');
        }

        return response()->json([
            'message' => 'Dispatch requested.',
            'job_id' => $job->id,
            'mode' => $mode,
        ], 202);
    }

    public function timeline(ServiceJob $job): JsonResponse
    {
        $this->authorize('view', $job);

        $timeline = $job->timeline()->orderBy('occurred_at')->get();

        return response()->json($timeline);
    }

    public function exceptions(ServiceJob $job): JsonResponse
    {
        $this->authorize('view', $job);

        $exceptions = $job->exceptions()->latest('detected_at')->get();

        return response()->json($exceptions);
    }
}
