<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;

class ExpandAuditFindingsAction
{
    public function __construct(
        protected CreateAuditProjectStepsAction $createAuditProjectStepsAction,
    ) {
    }

    /**
     * @return array{audit_phase_completed:bool,findings_count:int,followup_created:bool,followup_required:bool}
     */
    public function execute(AgentRun $run): array
    {
        $run = $run->fresh();
        $steps = $run->steps()->orderBy('id')->get();

        $auditSteps = $steps->filter(fn (AgentStep $step) => $this->phaseOf($step) === 'audit');
        if ($auditSteps->isEmpty() || ! $auditSteps->every(fn (AgentStep $step) => $step->status === AgentStepStatus::COMPLETED->value)) {
            return [
                'audit_phase_completed' => false,
                'findings_count' => 0,
                'followup_created' => false,
                'followup_required' => false,
            ];
        }

        $findingsCount = $this->resolveFindingsCount($run);
        $minFindings = max(1, (int) config('agent-os.audit.min_findings_for_followup', 1));
        $needsFollowup = $findingsCount >= $minFindings;
        $autoFollowup = (bool) data_get($run->metadata, 'auto_followup_on_findings', config('agent-os.audit.auto_followup_on_findings', true));
        $hasFollowup = $steps->contains(fn (AgentStep $step) => $this->phaseOf($step) === 'followup');

        $metadata = (array) $run->metadata;
        $metadata['audit_findings_count'] = $findingsCount;
        $run->metadata = $metadata;
        $run->save();

        if ($needsFollowup && $autoFollowup && ! $hasFollowup) {
            $this->createAuditProjectStepsAction->appendFollowupPhase($run);
            $metadata = (array) $run->fresh()->metadata;
            $metadata['followup_phase_created'] = true;
            $metadata['followup_trigger'] = 'findings_detected';
            $run->metadata = $metadata;
            $run->save();

            return [
                'audit_phase_completed' => true,
                'findings_count' => $findingsCount,
                'followup_created' => true,
                'followup_required' => false,
            ];
        }

        return [
            'audit_phase_completed' => true,
            'findings_count' => $findingsCount,
            'followup_created' => false,
            'followup_required' => $needsFollowup && ! $autoFollowup,
        ];
    }

    protected function resolveFindingsCount(AgentRun $run): int
    {
        return (int) $run->artifacts()
            ->whereIn('artifact_type', ['audit_step_report', 'final_audit_report'])
            ->get()
            ->sum(function ($artifact): int {
                return (int) data_get($artifact->metadata, 'findings_count', 0);
            });
    }

    protected function phaseOf(AgentStep $step): string
    {
        $phase = (string) data_get($step->metadata, 'phase', '');
        if ($phase !== '') {
            return $phase;
        }

        return in_array($step->step_type, [
            'documentation_audit',
            'architecture_review',
            'security_review',
            'ui_accessibility_review',
            'testing_cicd_review',
            'final_audit_report',
        ], true) ? 'audit' : 'followup';
    }
}
