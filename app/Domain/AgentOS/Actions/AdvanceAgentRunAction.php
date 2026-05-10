<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;

class AdvanceAgentRunAction
{
    public function __construct(
        protected UpdateAgentRunStatusAction $updateRunStatusAction,
        protected ExpandAuditFindingsAction $expandAuditFindingsAction,
    ) {
    }

    public function execute(AgentRun $run): AgentRun
    {
        $run = $run->fresh();
        $steps = $run->steps()->orderBy('id')->get();

        if ($steps->isEmpty()) {
            return $this->updateRunStatusAction->execute($run, AgentRunStatus::BLOCKED->value, [
                'terminal_reason' => 'no_steps_defined',
            ]);
        }

        $readyCount = $this->readyStepsCount($steps);

        $expansion = $this->expandAuditFindingsAction->execute($run);
        if ($expansion['followup_created']) {
            return $this->updateRunStatusAction->execute($run->fresh(), AgentRunStatus::EXECUTING->value, [
                'terminal_reason' => null,
            ]);
        }

        if ($expansion['audit_phase_completed'] && $expansion['followup_required']) {
            $this->syncFinalReportArtifact($run, ['final_audit_report']);

            return $this->updateRunStatusAction->execute($run, AgentRunStatus::FOLLOWUP_REQUIRED->value, [
                'terminal_reason' => 'findings_require_followup',
            ]);
        }

        if ($steps->every(fn (AgentStep $step) => $step->status === AgentStepStatus::COMPLETED->value)) {
            $completionGate = $this->evaluateCompletionGate($run, $steps);
            if (! $completionGate['allowed']) {
                return $this->updateRunStatusAction->execute($run, AgentRunStatus::FOLLOWUP_REQUIRED->value, [
                    'terminal_reason' => $completionGate['reason'],
                ]);
            }

            $finalArtifact = $run->artifacts()
                ->whereIn('artifact_type', ['final_delivery_package', 'final_audit_report'])
                ->latest('id')
                ->first();

            $metadata = (array) $run->fresh()->metadata;
            if ($finalArtifact) {
                $metadata['final_report_artifact_id'] = $finalArtifact->id;
            }
            $metadata['terminal_reason'] = 'all_steps_completed';

            $run->metadata = $metadata;
            $run->save();

            return $this->updateRunStatusAction->execute($run, AgentRunStatus::COMPLETED->value, [
                'terminal_reason' => 'all_steps_completed',
            ]);
        }

        if ($expansion['audit_phase_completed']) {
            $hasFollowupSteps = $steps->contains(fn (AgentStep $step) => $this->phaseOf($step) === 'followup');
            if (! $hasFollowupSteps && $readyCount === 0) {
                $this->syncFinalReportArtifact($run, ['final_audit_report']);

                return $this->updateRunStatusAction->execute($run, AgentRunStatus::AUDIT_COMPLETED->value, [
                    'terminal_reason' => 'audit_phase_completed_no_followup_required',
                ]);
            }
        }

        if ($steps->contains(fn (AgentStep $step) => $step->is_risky && $step->status === AgentStepStatus::READY_FOR_REVIEW->value)) {
            return $this->updateRunStatusAction->execute($run, AgentRunStatus::READY_FOR_REVIEW->value, [
                'terminal_reason' => 'risky_steps_require_approval',
            ]);
        }

        if ($readyCount === 0 && $steps->contains(fn (AgentStep $step) => $step->status === AgentStepStatus::FAILED->value)) {
            return $this->updateRunStatusAction->execute($run, AgentRunStatus::FAILED->value, [
                'terminal_reason' => 'failed_step_without_recovery',
            ]);
        }

        if ($readyCount === 0 && $steps->contains(fn (AgentStep $step) => $step->status === AgentStepStatus::BLOCKED->value)) {
            return $this->updateRunStatusAction->execute($run, AgentRunStatus::BLOCKED->value, [
                'terminal_reason' => 'blocked_step_dependency_or_resource',
            ]);
        }

        if ($readyCount > 0) {
            return $this->updateRunStatusAction->execute($run, AgentRunStatus::EXECUTING->value, [
                'terminal_reason' => null,
            ]);
        }

        return $this->updateRunStatusAction->execute($run, AgentRunStatus::BLOCKED->value, [
            'terminal_reason' => 'no_ready_steps_and_no_terminal_resolution',
        ]);
    }

    protected function phaseOf(AgentStep $step): string
    {
        return (string) data_get($step->metadata, 'phase', '');
    }

    /**
     * @param array<int,string> $artifactTypes
     */
    protected function syncFinalReportArtifact(AgentRun $run, array $artifactTypes): void
    {
        $artifact = $run->artifacts()
            ->whereIn('artifact_type', $artifactTypes)
            ->latest('id')
            ->first();

        if (! $artifact) {
            return;
        }

        $metadata = (array) $run->fresh()->metadata;
        $metadata['final_report_artifact_id'] = $artifact->id;
        $run->metadata = $metadata;
        $run->save();
    }

    protected function readyStepsCount($steps): int
    {
        $stepsById = $steps->keyBy('id');

        return $steps->filter(function (AgentStep $step) use ($stepsById): bool {
            if ($step->status !== AgentStepStatus::QUEUED->value) {
                return false;
            }

            $deps = array_values(array_filter((array) $step->depends_on));
            foreach ($deps as $depId) {
                $dep = $stepsById->get((int) $depId);
                if (! $dep || $dep->status !== AgentStepStatus::COMPLETED->value) {
                    return false;
                }
            }

            return true;
        })->count();
    }

    /**
     * @return array{allowed:bool,reason:string}
     */
    protected function evaluateCompletionGate(AgentRun $run, $steps): array
    {
        $activeStatuses = [
            AgentStepStatus::QUEUED->value,
            AgentStepStatus::EXECUTING->value,
            AgentStepStatus::WAITING_DEPENDENCIES->value,
            AgentStepStatus::VALIDATION_FAILED->value,
            AgentStepStatus::NEEDS_REVISION->value,
            AgentStepStatus::READY_FOR_REVIEW->value,
            AgentStepStatus::APPROVED->value,
        ];

        if ($steps->whereIn('status', $activeStatuses)->isNotEmpty()) {
            return ['allowed' => false, 'reason' => 'pending_or_inflight_steps_present'];
        }

        $flowType = (string) data_get($run->metadata, 'flow_type', 'audit_project');

        if ($flowType === 'ui_redesign' || $flowType === 'content_edit') {
            $requiredStepTypes = $flowType === 'content_edit'
                ? [
                    'page_discovery',
                    'browser_audit',
                    'content_redesign',
                    'target_resolution',
                    'content_update_execution',
                    'preview_capture',
                    'before_after_evidence',
                    'validation',
                ]
                : [
                    'page_discovery',
                    'browser_audit',
                    'research_benchmark',
                    'implementation_patch_plan',
                    'content_redesign',
                    'image_generation',
                    'template_or_code_patch_execution',
                    'preview_capture',
                    'before_after_evidence',
                    'quality_validation_bundle',
                    'final_delivery_package',
                ];

            $completedTypes = $steps
                ->where('status', AgentStepStatus::COMPLETED->value)
                ->pluck('step_type')
                ->unique()
                ->values()
                ->all();

            foreach ($requiredStepTypes as $requiredType) {
                if (! in_array($requiredType, $completedTypes, true)) {
                    return ['allowed' => false, 'reason' => 'missing_required_step_'.$requiredType];
                }
            }

            $artifacts = $run->artifacts()->get(['artifact_type', 'validation_status']);
            $artifactTypes = $artifacts->pluck('artifact_type')->unique()->values()->all();

            foreach ($requiredStepTypes as $requiredArtifactType) {
                if (! in_array($requiredArtifactType, $artifactTypes, true)) {
                    return ['allowed' => false, 'reason' => 'missing_required_artifact_'.$requiredArtifactType];
                }
            }

            $appliedChanges = $flowType === 'content_edit'
                ? $artifacts->whereIn('artifact_type', ['content_update_execution'])->count() > 0
                : $artifacts->whereIn('artifact_type', ['content_redesign', 'template_or_code_patch_execution'])->count() > 0;
            $evidenceReady = $artifacts->whereIn('artifact_type', ['preview_capture', 'before_after_evidence'])->count() >= 2;
            $validationReady = $flowType === 'content_edit'
                ? $artifacts->where('artifact_type', 'validation')->where('validation_status', 'pass')->count() > 0
                : $artifacts->where('artifact_type', 'quality_validation_bundle')->where('validation_status', 'pass')->count() > 0;

            if (! $appliedChanges) {
                return ['allowed' => false, 'reason' => 'missing_applied_changes'];
            }
            if (! $evidenceReady) {
                return ['allowed' => false, 'reason' => 'missing_before_after_evidence'];
            }
            if (! $validationReady) {
                return ['allowed' => false, 'reason' => 'missing_validation_artifacts'];
            }
        } else {
            $hasFinal = $run->artifacts()
                ->whereIn('artifact_type', ['final_delivery_package', 'final_audit_report'])
                ->exists();

            if (! $hasFinal) {
                return ['allowed' => false, 'reason' => 'missing_terminal_delivery_artifact'];
            }
        }

        return ['allowed' => true, 'reason' => 'ok'];
    }
}
