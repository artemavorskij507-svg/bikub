<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;

class CreateAuditProjectStepsAction
{
    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    public function execute(AgentRun $run)
    {
        if ($run->steps()->exists()) {
            return $run->steps()->orderBy('id')->get();
        }

        return $this->createPhaseSteps($run, $this->auditDefinitions());
    }

    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    public function appendFollowupPhase(AgentRun $run)
    {
        $existing = $run->steps()->where('metadata->phase', 'followup')->exists();
        if ($existing) {
            return $run->steps()->where('metadata->phase', 'followup')->orderBy('id')->get();
        }

        $anchorStep = $run->steps()->where('step_type', 'final_audit_report')->latest('id')->first();
        $anchorId = $anchorStep?->id;

        return $this->createPhaseSteps($run, $this->followupDefinitions(), $anchorId);
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    protected function createPhaseSteps(AgentRun $run, array $definitions, ?int $anchorStepId = null)
    {
        $created = collect();
        $prevId = $anchorStepId;

        foreach ($definitions as $definition) {
            $dependsOn = $prevId ? [$prevId] : [];

            $step = AgentStep::query()->create([
                'run_id' => $run->id,
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'step_type' => $definition['step_type'],
                'name' => $definition['name'],
                'status' => AgentStepStatus::QUEUED->value,
                'is_risky' => (bool) ($definition['is_risky'] ?? false),
                'depends_on' => $dependsOn,
                'input_payload' => [
                    'goal' => $run->goal,
                    'required_tool' => $definition['required_tool'] ?? 'none',
                ],
                'artifact_contract' => $definition['artifact_contract'],
                'max_retries' => 1,
                'metadata' => [
                    'source' => 'golden_flow_audit_project',
                    'phase' => $definition['phase'],
                ],
            ]);

            $created->push($step);
            $prevId = $step->id;
        }

        return $created;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function auditDefinitions(): array
    {
        return [
            [
                'step_type' => 'documentation_audit',
                'name' => 'Documentation audit',
                'required_tool' => 'research',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'documents_reviewed', 'findings', 'gaps', 'outdated_items', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'architecture_review',
                'name' => 'Architecture review',
                'required_tool' => 'research',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'modules_reviewed', 'findings', 'risks', 'bottlenecks', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'security_review',
                'name' => 'Security review',
                'required_tool' => 'security',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'checks_performed', 'findings', 'vulnerabilities', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'ui_accessibility_review',
                'name' => 'UI accessibility review',
                'required_tool' => 'browser',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'screens_reviewed', 'findings', 'accessibility_issues', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'testing_cicd_review',
                'name' => 'Testing and CI/CD review',
                'required_tool' => 'code',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'test_findings', 'pipeline_findings', 'coverage_gaps', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'final_audit_report',
                'name' => 'Final audit report',
                'required_tool' => 'none',
                'phase' => 'audit',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'findings', 'severity_map', 'remediation_backlog', 'recommendations', 'traceability', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function followupDefinitions(): array
    {
        return [
            [
                'step_type' => 'remediation_backlog',
                'name' => 'Remediation backlog',
                'required_tool' => 'research',
                'phase' => 'followup',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'backlog_items', 'priorities', 'dependencies', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'priority_scoring',
                'name' => 'Priority scoring',
                'required_tool' => 'research',
                'phase' => 'followup',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'scoring_method', 'scored_items', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'implementation_plan',
                'name' => 'Implementation plan',
                'required_tool' => 'code',
                'phase' => 'followup',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'milestones', 'owners', 'execution_tasks', 'validation_rerun', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'final_delivery_package',
                'name' => 'Final delivery package',
                'required_tool' => 'none',
                'phase' => 'followup',
                'is_risky' => false,
                'artifact_contract' => [
                    'required_sections' => ['summary', 'delivery_scope', 'backlog_snapshot', 'validation_rerun', 'traceability', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
        ];
    }
}

