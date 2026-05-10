<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Models\AgentRun;

class StartAgentRunAction
{
    public function __construct(
        protected CreateAgentRunAction $createRunAction,
        protected CreateGoalDrivenStepsAction $createStepsAction,
        protected UpdateAgentRunStatusAction $updateRunStatusAction,
        protected \App\Domain\AgentOS\Services\AgentWorkspaceEventService $workspaceEventService,
    ) {
    }

    public function execute(array $payload): AgentRun
    {
        $metadata = (array) ($payload['metadata'] ?? []);
        $metadata['auto_followup_on_findings'] = array_key_exists('auto_followup_on_findings', $metadata)
            ? (bool) $metadata['auto_followup_on_findings']
            : (bool) config('agent-os.audit.auto_followup_on_findings', true);
        $payload['metadata'] = $metadata;

        $run = $this->createRunAction->execute($payload);

        if ($run->steps()->count() === 0) {
            $this->updateRunStatusAction->execute($run, AgentRunStatus::PLANNING->value);
            $this->createStepsAction->execute($run);
            $run = $run->fresh();
        }

        $this->workspaceEventService->ensureThreads($run);
        $this->workspaceEventService->append(
            run: $run,
            eventType: 'run_started',
            message: sprintf('Run %d started: %s', $run->id, (string) $run->goal),
            threadKey: 'main',
            payload: [
                'status' => $run->status,
                'risk_level' => $run->risk_level,
                'requires_approval' => (bool) $run->requires_approval,
            ],
            actorType: 'director',
            actorKey: 'Director',
            eventLevel: 'info',
            dedupeKey: 'run_started'
        );

        return $run;
    }
}

