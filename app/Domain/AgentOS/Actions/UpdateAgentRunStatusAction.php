<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Events\AgentRunProgressUpdated;
use App\Domain\AgentOS\Events\AgentRunTerminalReached;
use App\Domain\AgentOS\Models\AgentRun;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class UpdateAgentRunStatusAction
{
    public function execute(AgentRun $run, string $toStatus, array $context = []): AgentRun
    {
        $fromStatus = (string) $run->status;
        if ($fromStatus === $toStatus) {
            return $run;
        }

        $deployStatuses = [
            AgentRunStatus::DEPLOY_READY->value,
            AgentRunStatus::DEPLOYING->value,
            AgentRunStatus::DEPLOYED->value,
            AgentRunStatus::ROLLBACK_REQUIRED->value,
        ];

        if (in_array($toStatus, $deployStatuses, true)) {
            $target = (string) ($context['deploy_target'] ?? 'staging');
            $stagingEnabled = (bool) config('agent-os.feature_flags.deploy_staging', false);
            $productionEnabled = (bool) config('agent-os.feature_flags.deploy_production', false);

            if (! $stagingEnabled) {
                throw new InvalidArgumentException('Deploy statuses are disabled by AGENT_OS_DEPLOY_STAGING flag.');
            }

            if ($target === 'production' && ! $productionEnabled) {
                throw new InvalidArgumentException('Production deploy is disabled by AGENT_OS_DEPLOY_PRODUCTION flag.');
            }
        }

        $run->status = $toStatus;
        $run->updated_by = $context['actor_id'] ?? $run->updated_by;
        if (array_key_exists('terminal_reason', $context)) {
            $run->terminal_reason = $context['terminal_reason'];
        }

        if ($toStatus === AgentRunStatus::EXECUTING->value && $run->started_at === null) {
            $run->started_at = now();
        }

        if (in_array($toStatus, [
            AgentRunStatus::COMPLETED->value,
            AgentRunStatus::FAILED->value,
            AgentRunStatus::BLOCKED->value,
            AgentRunStatus::READY_FOR_REVIEW->value,
            AgentRunStatus::AUDIT_COMPLETED->value,
            AgentRunStatus::FOLLOWUP_REQUIRED->value,
        ], true)) {
            $run->finished_at = now();
        }

        $run->save();

        $run = $run->fresh();

        event(new AgentRunProgressUpdated(
            organizationId: (string) ($run->organization_id ?? 'global'),
            runId: (int) $run->id,
            payload: [
                'from' => $fromStatus,
                'to' => $toStatus,
                'terminal_reason' => $run->terminal_reason,
                'risk_level' => $run->risk_level,
            ]
        ));

        if ($this->isTerminal($toStatus)) {
            event(new AgentRunTerminalReached(
                organizationId: (string) ($run->organization_id ?? 'global'),
                runId: (int) $run->id,
                payload: [
                    'status' => $toStatus,
                    'terminal_reason' => $run->terminal_reason,
                    'final_report_artifact_id' => data_get($run->metadata, 'final_report_artifact_id'),
                ],
            ));
        }

        if ($this->isAuditedTransition($toStatus)) {
            Log::info('AgentOS run status transition', [
                'run_id' => $run->id,
                'from' => $fromStatus,
                'to' => $toStatus,
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'actor_id' => $context['actor_id'] ?? null,
                'terminal_reason' => $run->terminal_reason,
            ]);
        }

        return $run;
    }

    protected function isTerminal(string $status): bool
    {
        return in_array($status, [
            AgentRunStatus::COMPLETED->value,
            AgentRunStatus::FAILED->value,
            AgentRunStatus::BLOCKED->value,
            AgentRunStatus::READY_FOR_REVIEW->value,
            AgentRunStatus::AUDIT_COMPLETED->value,
            AgentRunStatus::FOLLOWUP_REQUIRED->value,
        ], true);
    }

    protected function isAuditedTransition(string $status): bool
    {
        return in_array($status, [
            AgentRunStatus::READY_FOR_REVIEW->value,
            AgentRunStatus::APPROVED->value,
            AgentRunStatus::COMPLETED->value,
            AgentRunStatus::FAILED->value,
            AgentRunStatus::BLOCKED->value,
            AgentRunStatus::DEPLOY_READY->value,
            AgentRunStatus::DEPLOYING->value,
            AgentRunStatus::DEPLOYED->value,
            AgentRunStatus::ROLLBACK_REQUIRED->value,
        ], true);
    }
}
