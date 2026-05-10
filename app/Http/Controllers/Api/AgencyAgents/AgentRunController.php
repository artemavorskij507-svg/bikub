<?php

namespace App\Http\Controllers\Api\AgencyAgents;

use App\Domain\AgentOS\Jobs\ProcessAgentRunJob;
use App\Domain\AgentOS\Actions\StartAgentRunAction;
use App\Domain\AgentOS\Actions\UpdateAgentRunStatusAction;
use App\Domain\AgentOS\Models\AgentArtifact;
use App\Domain\AgentOS\Models\AgentRunEvent;
use App\Domain\AgentOS\Models\AgentRunThread;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Services\AgentRunSummaryService;
use App\Domain\AgentOS\Services\AgentWorkspaceEventService;
use App\Domain\AgentOS\Services\RunOrchestratorService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyAgents\StoreAgentRunRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class AgentRunController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgentRun::class);

        $query = AgentRun::query()
            ->when(! $this->isAdmin($request), fn ($q) => $this->applyOwnershipScope($q, $request))
            ->when($request->string('risk_level')->toString(), fn ($q, $value) => $q->where('risk_level', $value))
            ->when($request->has('requires_approval'), fn ($q) => $q->where('requires_approval', $request->boolean('requires_approval')))
            ->when($request->string('status')->toString(), fn ($q, $value) => $q->where('status', $value))
            ->latest('id');

        return response()->json($query->paginate(min((int) $request->integer('per_page', 25), 100)));
    }

    public function store(
        StoreAgentRunRequest $request,
        StartAgentRunAction $startAction,
        RunOrchestratorService $orchestrator,
        AgentRunSummaryService $summaryService
    ): JsonResponse {
        $payload = $request->validated();
        $payload['created_by'] = $request->user()?->id;
        $payload['updated_by'] = $request->user()?->id;
        $payload['goal'] = $this->sanitizeGoal((string) ($payload['goal'] ?? 'Project audit'));
        $payload['organization_id'] = $this->userOrganizationId($request);
        $payload['tenant_id'] = $this->userTenantId($request);
        $payload['idempotency_key'] = $payload['idempotency_key']
            ?? sha1($payload['goal'].'|'.$payload['created_by'].'|'.now()->format('YmdHi'));

        $run = $startAction->execute($payload);
        $this->authorize('view', $run);

        $queue = 'default';
        $connection = (string) config('agent-os.api.connection', 'redis');
        $asyncEnabled = (bool) config('agent-os.api.async_enabled', true);

        if ($asyncEnabled) {
            ProcessAgentRunJob::dispatch($run->id, $request->user()?->id)
                ->onConnection($connection)
                ->onQueue($queue);
        } else {
            $mode = (string) config('agent-os.execution_mode', 'sync');
            if ($mode === 'sync') {
                $run = $orchestrator->run($run, ['actor_id' => $request->user()?->id]);
            }
        }

        $run = $run->fresh(['steps', 'artifacts', 'validations', 'memories']);
        $summary = $summaryService->build($run);

        return response()->json([
            'run' => $run,
            'summary' => $summary,
        ], $asyncEnabled ? Response::HTTP_ACCEPTED : Response::HTTP_CREATED);
    }

    public function show(Request $request, AgentRun $run, AgentRunSummaryService $summaryService): JsonResponse
    {
        $this->authorize('view', $run);

        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $run->load(['steps', 'artifacts', 'validations', 'memories']);

        return response()->json([
            'run' => $run,
            'summary' => $summaryService->build($run),
        ]);
    }

    public function workspace(Request $request, AgentRun $run, AgentRunSummaryService $summaryService, AgentWorkspaceEventService $workspaceEventService): JsonResponse
    {
        $this->authorize('view', $run);
        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $workspaceEventService->ensureThreads($run);
        $run->loadCount([
            'artifacts as artifact_count',
            'steps as blockers_count' => fn ($q) => $q->where('status', 'blocked'),
            'steps as approvals_pending' => fn ($q) => $q->where('status', 'ready_for_review'),
        ]);

        $summary = $summaryService->build($run->fresh());

        $activeAgents = AgentRunEvent::query()
            ->where('run_id', $run->id)
            ->whereNotNull('actor_key')
            ->whereIn('actor_type', ['agent', 'director'])
            ->whereIn('event_type', [
                'run_started',
                'director_decision',
                'delegation_created',
                'task_delegated',
                'tool_call_started',
                'tool_call_finished',
                'tool_action',
                'artifact_created',
                'finding_detected',
                'finding',
                'step_completed',
                'validation_failed',
                'approval_required',
                'blocked_reason',
                'revision_requested',
            ])
            ->latest('id')
            ->limit(80)
            ->pluck('actor_key')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'run_id' => $run->id,
            'goal' => $run->goal,
            'status' => $run->status,
            'summary' => $summary,
            'run_state' => data_get($run->fresh()->metadata, 'run_state', []),
            'risk_level' => $run->risk_level,
            'requires_approval' => (bool) $run->requires_approval,
            'artifact_count' => (int) $run->artifact_count,
            'blockers_count' => (int) $run->blockers_count,
            'approvals_pending' => (int) $run->approvals_pending,
            'active_agents' => $activeAgents,
            'confidence_level' => data_get($summary, 'confidence_level', 'medium'),
            'updated_at' => optional($run->updated_at)->toIso8601String(),
        ]);
    }

    public function threads(Request $request, AgentRun $run, AgentWorkspaceEventService $workspaceEventService): JsonResponse
    {
        $this->authorize('view', $run);
        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $workspaceEventService->ensureThreads($run);
        $threads = AgentRunThread::query()
            ->where('run_id', $run->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($thread) => [
                'id' => $thread->id,
                'thread_key' => $thread->thread_key,
                'title' => $thread->title,
                'is_system' => (bool) $thread->is_system,
                'events_count' => AgentRunEvent::query()->where('thread_id', $thread->id)->count(),
            ])
            ->values();

        return response()->json($threads);
    }

    public function events(Request $request, AgentRun $run): JsonResponse
    {
        $this->authorize('view', $run);
        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $thread = $request->string('thread')->toString();
        $query = AgentRunEvent::query()
            ->where('run_id', $run->id)
            ->with('thread:id,thread_key,title')
            ->when($thread !== '', function ($q) use ($thread): void {
                $q->whereHas('thread', fn ($t) => $t->where('thread_key', $thread));
            })
            ->orderBy('id');

        return response()->json($query->paginate(min((int) $request->integer('per_page', 200), 500)));
    }

    public function artifacts(Request $request, AgentRun $run): JsonResponse
    {
        $this->authorize('view', $run);
        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $query = AgentArtifact::query()
            ->where('run_id', $run->id)
            ->when($request->string('type')->toString(), fn ($q, $value) => $q->where('artifact_type', $value))
            ->latest('id');

        return response()->json($query->paginate(min((int) $request->integer('per_page', 100), 200)));
    }

    public function updateStatus(Request $request, AgentRun $run, UpdateAgentRunStatusAction $action): JsonResponse
    {
        $this->authorize('updateStatus', $run);

        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($run, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:queued,planning,executing,waiting_dependencies,validation_failed,needs_revision,ready_for_review,approved,audit_completed,followup_required,completed,blocked,failed,deploy_ready,deploying,deployed,rollback_required'],
            'deploy_target' => ['nullable', 'in:staging,production'],
        ]);

        $updated = $action->execute($run, $validated['status'], [
            'deploy_target' => $validated['deploy_target'] ?? 'staging',
            'actor_id' => $request->user()?->id,
        ]);

        return response()->json($updated);
    }

    protected function applyOwnershipScope($query, Request $request): void
    {
        $query->where('organization_id', $this->userOrganizationId($request))
            ->where('tenant_id', $this->userTenantId($request));
    }

    protected function userOrganizationId(Request $request): ?string
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $user->organization_id ?? $user->default_org_id ?? null;
    }

    protected function userTenantId(Request $request): ?int
    {
        $value = $request->user()?->tenant_id ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function belongsToCurrentContext(AgentRun $run, Request $request): bool
    {
        return (string) ($run->organization_id ?? '') === (string) ($this->userOrganizationId($request) ?? '')
            && (string) ($run->tenant_id ?? '') === (string) ($this->userTenantId($request) ?? '');
    }

    protected function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function sanitizeGoal(string $goal): string
    {
        $value = trim(strip_tags($goal));
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return mb_substr($value, 0, 5000);
    }
}
