<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Events\AgentStepStatusChanged;
use App\Domain\AgentOS\Models\AgentArtifact;
use App\Domain\AgentOS\Models\AgentStep;
use App\Domain\AgentOS\Policies\StepTransitionPolicy;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class UpdateAgentStepStatusAction
{
    public function execute(AgentStep $step, string $toStatus, array $context = []): AgentStep
    {
        $fromStatus = (string) $step->status;
        if ($fromStatus === $toStatus) {
            return $step;
        }

        if (! StepTransitionPolicy::canTransition($fromStatus, $toStatus)) {
            throw new InvalidArgumentException("Invalid step status transition: {$fromStatus} -> {$toStatus}");
        }

        if ($step->is_risky && $toStatus === AgentStepStatus::COMPLETED->value && $fromStatus !== AgentStepStatus::APPROVED->value) {
            throw new InvalidArgumentException('Risky step cannot transition directly to completed without approved status.');
        }

        if ($fromStatus === AgentStepStatus::ARTIFACT_GENERATED->value && $toStatus === AgentStepStatus::READY_FOR_REVIEW->value) {
            if (($context['validator_passed'] ?? false) !== true) {
                throw new InvalidArgumentException('validator_passed=true is required for artifact_generated -> ready_for_review.');
            }
        }

        if ($fromStatus === AgentStepStatus::ARTIFACT_GENERATED->value && $toStatus === AgentStepStatus::VALIDATION_FAILED->value) {
            if (($context['validator_passed'] ?? null) === true) {
                throw new InvalidArgumentException('Cannot set validation_failed when validator_passed=true.');
            }
        }

        $step->status = $toStatus;

        if ($toStatus === AgentStepStatus::EXECUTING->value) {
            $step->started_at ??= now();
            $step->heartbeat_at = now();
            if ($step->timeout_at === null) {
                $defaultMinutes = (int) config('agent-os.timeout.default_step_minutes', 15);
                $step->timeout_at = now()->addMinutes(max(1, $defaultMinutes));
            }
        }

        if (in_array($toStatus, [AgentStepStatus::COMPLETED->value, AgentStepStatus::FAILED->value, AgentStepStatus::BLOCKED->value], true)) {
            $step->finished_at = now();
        }

        if (isset($context['output_payload']) && is_array($context['output_payload'])) {
            $step->output_payload = $context['output_payload'];
        }

        if (isset($context['validation_notes'])) {
            $step->validation_notes = (string) $context['validation_notes'];
        }

        if (array_key_exists('reduced_confidence', $context)) {
            $step->reduced_confidence = (bool) $context['reduced_confidence'];
        }

        if (array_key_exists('confidence_reason', $context)) {
            $step->confidence_reason = $context['confidence_reason'];
        }

        if (array_key_exists('validation_result', $context)) {
            $step->validation_result = $context['validation_result'];
        }

        $step->save();
        $step = $step->fresh();

        if (in_array($toStatus, [AgentStepStatus::BLOCKED->value, AgentStepStatus::FAILED->value], true)) {
            AgentArtifact::query()->create([
                'run_id' => $step->run_id,
                'step_id' => $step->id,
                'organization_id' => $step->organization_id,
                'tenant_id' => $step->tenant_id,
                'artifact_type' => 'system_note',
                'content' => (string) ($context['system_note'] ?? 'Step moved to '.$toStatus),
                'validation_status' => $toStatus,
                'metadata' => [
                    'from' => $fromStatus,
                    'to' => $toStatus,
                ],
            ]);
        }

        event(new AgentStepStatusChanged(
            organizationId: (string) ($step->organization_id ?? 'global'),
            runId: (int) $step->run_id,
            stepId: (int) $step->id,
            payload: [
                'from' => $fromStatus,
                'to' => $toStatus,
                'validation_result' => $step->validation_result,
                'retry_count' => $step->retry_count,
                'is_risky' => (bool) $step->is_risky,
                'heartbeat_at' => optional($step->heartbeat_at)->toIso8601String(),
                'timeout_at' => optional($step->timeout_at)->toIso8601String(),
            ]
        ));

        if ($this->isAuditedStepTransition($toStatus)) {
            Log::info('AgentOS step status transition', [
                'step_id' => $step->id,
                'run_id' => $step->run_id,
                'from' => $fromStatus,
                'to' => $toStatus,
                'organization_id' => $step->organization_id,
                'tenant_id' => $step->tenant_id,
                'validation_result' => $step->validation_result,
            ]);
        }

        return $step;
    }

    protected function isAuditedStepTransition(string $status): bool
    {
        return in_array($status, [
            AgentStepStatus::READY_FOR_REVIEW->value,
            AgentStepStatus::APPROVED->value,
            AgentStepStatus::COMPLETED->value,
            AgentStepStatus::BLOCKED->value,
            AgentStepStatus::FAILED->value,
            AgentStepStatus::VALIDATION_FAILED->value,
        ], true);
    }
}
