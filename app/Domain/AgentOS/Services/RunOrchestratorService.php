<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Actions\AdvanceAgentRunAction;
use App\Domain\AgentOS\Actions\ExecuteReadyAgentStepsAction;
use App\Domain\AgentOS\Actions\UpdateAgentRunStatusAction;
use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;

class RunOrchestratorService
{
    public function __construct(
        protected ExecuteReadyAgentStepsAction $executeReadyAgentStepsAction,
        protected AdvanceAgentRunAction $advanceAgentRunAction,
        protected UpdateAgentRunStatusAction $updateAgentRunStatusAction,
        protected AgentWorkspaceEventService $workspaceEventService,
    ) {
    }

    public function run(AgentRun $run, array $context = []): AgentRun
    {
        $maxIterations = (int) ($context['max_iterations'] ?? config('agent-os.loop.max_iterations', 50));
        $maxIterations = max(1, $maxIterations);
        $planningStaleSeconds = max(5, (int) config('agent-os.chat.planning_stale_seconds', 20));

        $run = $run->fresh();
        if (
            (string) $run->status === AgentRunStatus::PLANNING->value
            && optional($run->updated_at)?->lte(now()->subSeconds($planningStaleSeconds))
            && $run->steps()->where('status', AgentStepStatus::QUEUED->value)->exists()
        ) {
            $this->updateAgentRunStatusAction->execute($run, AgentRunStatus::EXECUTING->value, [
                'actor_id' => $context['actor_id'] ?? null,
                'terminal_reason' => null,
            ]);
        }

        $this->updateAgentRunStatusAction->execute($run, AgentRunStatus::EXECUTING->value, [
            'actor_id' => $context['actor_id'] ?? null,
            'terminal_reason' => null,
        ]);
        $this->updateRunState($run, [
            'active_phase' => 'execution',
            'open_blockers' => [],
            'unresolved_items' => [],
            'accepted_decisions' => ['run_started', 'execution_mode_sync_first'],
        ]);

        for ($i = 0; $i < $maxIterations; $i++) {
            $run = $run->fresh();
            $this->workspaceEventService->append(
                run: $run,
                eventType: 'director_decision',
                message: sprintf('Director loop iteration %d: dispatching ready steps.', $i + 1),
                threadKey: 'main',
                payload: [
                    'iteration' => $i + 1,
                    'status_before' => $run->status,
                ],
                actorType: 'director',
                actorKey: 'Director',
                eventLevel: 'info'
            );
            $executed = $this->executeReadyAgentStepsAction->execute($run);
            $run = $this->advanceAgentRunAction->execute($run);
            $this->updateRunState($run, [
                'active_phase' => $this->resolveActivePhase($run),
                'open_blockers' => $run->steps()->where('status', AgentStepStatus::BLOCKED->value)->pluck('step_type')->values()->all(),
                'unresolved_items' => $run->steps()->whereIn('status', [
                    AgentStepStatus::QUEUED->value,
                    AgentStepStatus::WAITING_DEPENDENCIES->value,
                    AgentStepStatus::NEEDS_REVISION->value,
                    AgentStepStatus::READY_FOR_REVIEW->value,
                ])->pluck('step_type')->values()->all(),
                'accepted_decisions' => ['dispatch_next_ready_steps'],
            ]);

            if ($this->isTerminal($run->status)) {
                return $run;
            }

            if ($executed === 0) {
                $hasInFlightSteps = $run->steps()
                    ->whereIn('status', [
                        AgentStepStatus::EXECUTING->value,
                        AgentStepStatus::WAITING_DEPENDENCIES->value,
                    ])
                    ->exists();

                if ($hasInFlightSteps) {
                    return $run;
                }

                return $this->updateAgentRunStatusAction->execute($run, AgentRunStatus::BLOCKED->value, [
                    'terminal_reason' => 'no_progress_in_iteration',
                    'actor_id' => $context['actor_id'] ?? null,
                ]);
            }
        }

        return $this->updateAgentRunStatusAction->execute($run->fresh(), AgentRunStatus::BLOCKED->value, [
            'terminal_reason' => 'max_iterations_reached',
            'actor_id' => $context['actor_id'] ?? null,
        ]);
    }

    protected function updateRunState(AgentRun $run, array $state): void
    {
        $fresh = $run->fresh();
        if (! $fresh) {
            return;
        }

        $metadata = (array) ($fresh->metadata ?? []);
        $existingState = (array) ($metadata['run_state'] ?? []);
        $metadata['run_state'] = array_merge($existingState, $state, [
            'goal' => (string) $fresh->goal,
            'produced_artifacts' => $fresh->artifacts()->latest('id')->limit(20)->pluck('artifact_type')->values()->all(),
            'updated_at' => now()->toIso8601String(),
        ]);
        $fresh->metadata = $metadata;
        $fresh->save();
    }

    protected function resolveActivePhase(AgentRun $run): string
    {
        $step = $run->steps()
            ->whereIn('status', [
                AgentStepStatus::EXECUTING->value,
                AgentStepStatus::QUEUED->value,
                AgentStepStatus::WAITING_DEPENDENCIES->value,
            ])
            ->orderBy('id')
            ->first();

        return (string) data_get($step?->metadata, 'phase', $run->status);
    }

    protected function isTerminal(string $status): bool
    {
        return in_array($status, [
            AgentRunStatus::COMPLETED->value,
            AgentRunStatus::BLOCKED->value,
            AgentRunStatus::FAILED->value,
            AgentRunStatus::READY_FOR_REVIEW->value,
            AgentRunStatus::AUDIT_COMPLETED->value,
            AgentRunStatus::FOLLOWUP_REQUIRED->value,
        ], true);
    }
}
