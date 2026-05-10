<?php

namespace App\Http\Controllers\Api\AgencyAgents;

use App\Domain\AgentOS\Actions\UpdateAgentStepStatusAction;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyAgents\UpdateAgentStepStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class AgentStepController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgentStep::class);

        $query = AgentStep::query()
            ->with('run:id,status,risk_level')
            ->when(! $this->isAdmin($request), fn ($q) => $this->applyOwnershipScope($q, $request))
            ->when($request->string('status')->toString(), fn ($q, $value) => $q->where('status', $value))
            ->when($request->filled('run_id'), fn ($q) => $q->where('run_id', (int) $request->integer('run_id')))
            ->when($request->boolean('stale'), function ($q): void {
                $grace = max(1, (int) config('agent-os.timeout.heartbeat_grace_minutes', 2));
                $threshold = now()->subMinutes($grace);

                $q->where('status', AgentStepStatus::EXECUTING->value)
                    ->where(function ($inner) use ($threshold): void {
                        $inner->where(function ($hb) use ($threshold): void {
                            $hb->whereNotNull('heartbeat_at')
                                ->where('heartbeat_at', '<=', $threshold);
                        })->orWhere(function ($hb): void {
                            $hb->whereNull('heartbeat_at')
                                ->whereNotNull('started_at')
                                ->where('started_at', '<=', now()->subMinutes(2));
                        });
                    });
            })
            ->when($request->boolean('timed_out'), function ($q): void {
                $q->where('status', AgentStepStatus::EXECUTING->value)
                    ->whereNotNull('timeout_at')
                    ->where('timeout_at', '<=', now());
            })
            ->latest('id');

        return response()->json($query->paginate(min((int) $request->integer('per_page', 25), 100)));
    }

    public function show(Request $request, AgentStep $step): JsonResponse
    {
        $this->authorize('view', $step);

        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($step, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $step->load(['run', 'artifacts', 'validations']);

        return response()->json($step);
    }

    public function updateStatus(UpdateAgentStepStatusRequest $request, AgentStep $step, UpdateAgentStepStatusAction $action): JsonResponse
    {
        $this->authorize('updateStatus', $step);

        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($step, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $updated = $action->execute($step, $request->validated('status'), $request->validated());

        return response()->json($updated);
    }

    public function heartbeat(Request $request, AgentStep $step): JsonResponse
    {
        $this->authorize('heartbeat', $step);

        if (! $this->isAdmin($request) && ! $this->belongsToCurrentContext($step, $request)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($step->status === AgentStepStatus::EXECUTING->value) {
            $step->heartbeat_at = now();
            $step->save();
        }

        return response()->json([
            'id' => $step->id,
            'status' => $step->status,
            'heartbeat_at' => optional($step->heartbeat_at)->toIso8601String(),
        ]);
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

    protected function belongsToCurrentContext(AgentStep $step, Request $request): bool
    {
        return (string) ($step->organization_id ?? '') === (string) ($this->userOrganizationId($request) ?? '')
            && (string) ($step->tenant_id ?? '') === (string) ($this->userTenantId($request) ?? '');
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
}
