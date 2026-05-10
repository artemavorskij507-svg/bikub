<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;
use Illuminate\Support\Facades\Cache;

class AgentRunSummaryService
{
    /**
     * @return array<string,mixed>
     */
    public function build(AgentRun $run): array
    {
        $signature = $this->signature($run);
        $cacheKey = sprintf('agent-os:run-summary:%d:%s', $run->id, $signature);

        return Cache::remember($cacheKey, now()->addSeconds(15), function () use ($run): array {
            $steps = $run->steps()->orderBy('id')->get();
            $stepsById = $steps->keyBy('id');
            $artifacts = $run->artifacts()->get(['id', 'artifact_type', 'metadata', 'validation_status']);
            $validations = $run->validations()->get(['result', 'score', 'metadata']);

            $total = $steps->count();
            $completed = $steps->where('status', AgentStepStatus::COMPLETED->value)->count();
            $progress = $total > 0 ? (int) floor(($completed / $total) * 100) : 0;

            $ready = $steps->filter(function (AgentStep $step) use ($stepsById): bool {
                if ($step->status !== AgentStepStatus::QUEUED->value) {
                    return false;
                }

                foreach ((array) $step->depends_on as $depId) {
                    $dep = $stepsById->get((int) $depId);
                    if (! $dep || $dep->status !== AgentStepStatus::COMPLETED->value) {
                        return false;
                    }
                }

                return true;
            })->count();

            $activeStep = $steps->first(function (AgentStep $step): bool {
                return in_array($step->status, [
                    AgentStepStatus::QUEUED->value,
                    AgentStepStatus::WAITING_DEPENDENCIES->value,
                    AgentStepStatus::EXECUTING->value,
                    AgentStepStatus::ARTIFACT_GENERATED->value,
                    AgentStepStatus::VALIDATION_FAILED->value,
                    AgentStepStatus::NEEDS_REVISION->value,
                    AgentStepStatus::READY_FOR_REVIEW->value,
                    AgentStepStatus::APPROVED->value,
                ], true);
            });
            $activePhase = (string) data_get($activeStep?->metadata, 'phase', $run->status);
            $lastStepAt = optional($steps->max('updated_at'))->toIso8601String();
            $artifactCount = (int) $artifacts->count();
            $blockersCount = $steps->where('status', AgentStepStatus::BLOCKED->value)->count();
            $approvalsPending = $steps->where('status', AgentStepStatus::READY_FOR_REVIEW->value)->count();
            $hasSyntheticEvidence = $artifacts->contains(function ($artifact): bool {
                return (bool) data_get($artifact->metadata, 'synthetic_detected', false)
                    || count((array) data_get($artifact->metadata, 'synthetic_hits', [])) > 0;
            });
            $hasWeakEvidence = $artifacts->contains(function ($artifact): bool {
                return (bool) data_get($artifact->metadata, 'weak_evidence', false)
                    || (bool) data_get($artifact->metadata, 'reduced_confidence', false);
            });
            $hasValidationFailure = $validations->contains(fn ($v): bool => (string) $v->result === 'fail');
            $hasValidationPass = $validations->contains(fn ($v): bool => (string) $v->result === 'pass');
            $hasStepFailures = $steps->where('status', AgentStepStatus::FAILED->value)->count() > 0
                || $steps->where('status', AgentStepStatus::NEEDS_REVISION->value)->count() > 0
                || $steps->where('status', AgentStepStatus::VALIDATION_FAILED->value)->count() > 0;

            $confidenceLevel = 'medium';
            $isBlockedTerminal = in_array((string) $run->status, ['blocked', 'failed'], true);
            if ($hasSyntheticEvidence || $hasValidationFailure || $hasStepFailures || $isBlockedTerminal) {
                $confidenceLevel = 'low';
            } elseif (! $hasWeakEvidence && $hasValidationPass) {
                $confidenceLevel = 'high';
            }

            return [
                'run_id' => $run->id,
                'goal' => (string) $run->goal,
                'terminal_status' => (string) $run->status,
                'terminal_reason' => $run->terminal_reason,
                'risk_level' => $run->risk_level,
                'requires_approval' => (bool) $run->requires_approval,
                'deployment_allowed' => (bool) $run->deployment_allowed,
                'idempotency_key' => $run->idempotency_key,
                'final_report_artifact_id' => data_get($run->metadata, 'final_report_artifact_id'),
                'audit_findings_count' => (int) data_get($run->metadata, 'audit_findings_count', 0),
                'followup_phase_created' => (bool) data_get($run->metadata, 'followup_phase_created', false),
                'steps_total' => $total,
                'steps_ready' => $ready,
                'steps_completed' => $completed,
                'steps_blocked' => $blockersCount,
                'steps_failed' => $steps->where('status', AgentStepStatus::FAILED->value)->count(),
                'steps_needs_revision' => $steps->where('status', AgentStepStatus::NEEDS_REVISION->value)->count(),
                'steps_ready_for_review' => $approvalsPending,
                'progress_percent' => $progress,
                'active_phase' => $activePhase,
                'active_step_type' => $activeStep?->step_type,
                'active_step_status' => $activeStep?->status,
                'last_step_at' => $lastStepAt,
                'artifact_count' => $artifactCount,
                'blockers_count' => $blockersCount,
                'approvals_pending' => $approvalsPending,
                'confidence_level' => $confidenceLevel,
                'evidence_quality' => [
                    'has_synthetic' => $hasSyntheticEvidence,
                    'has_weak_evidence' => $hasWeakEvidence,
                    'has_validation_failure' => $hasValidationFailure,
                    'has_validation_pass' => $hasValidationPass,
                ],
                'execution_connection' => (string) config('agent-os.chat.connection', 'redis'),
                'started_at' => optional($run->started_at)->toIso8601String(),
                'finished_at' => optional($run->finished_at)->toIso8601String(),
            ];
        });
    }

    protected function signature(AgentRun $run): string
    {
        $runTs = optional($run->updated_at)->getTimestamp() ?? 0;
        $stepsTs = optional($run->steps()->max('updated_at'))->getTimestamp() ?? 0;
        $artifactsTs = optional($run->artifacts()->max('updated_at'))->getTimestamp() ?? 0;
        $validationsTs = optional($run->validations()->max('updated_at'))->getTimestamp() ?? 0;

        return sha1(implode('|', [$runTs, $stepsTs, $artifactsTs, $validationsTs]));
    }
}
