<?php

namespace App\Domain\AgentOS\Actions;

use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;

class CreateGoalDrivenStepsAction
{
    public function __construct(
        protected CreateAuditProjectStepsAction $createAuditProjectStepsAction,
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    public function execute(AgentRun $run)
    {
        if ($run->steps()->exists()) {
            return $run->steps()->orderBy('id')->get();
        }

        if ($this->isLightweightChatGoal((string) $run->goal)) {
            return $this->createLightweightChatFlow($run);
        }

        if ($this->isSimpleContentChangeGoal((string) $run->goal)) {
            return $this->createContentEditFlow($run);
        }

        if ($this->isUiRedesignGoal((string) $run->goal)) {
            return $this->createUiRedesignFlow($run);
        }

        $steps = $this->createAuditProjectStepsAction->execute($run);

        $metadata = (array) $run->metadata;
        $metadata['flow_type'] = $metadata['flow_type'] ?? 'audit_project';
        $run->metadata = $metadata;
        $run->save();

        return $steps;
    }

    protected function isLightweightChatGoal(string $goal): bool
    {
        $normalized = mb_strtolower(trim($goal));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?: '';

        if ($normalized === '') {
            return true;
        }

        if (preg_match('/https?:\/\//iu', $normalized) === 1) {
            return false;
        }

        $smallTalk = [
            'привет',
            'здравствуйте',
            'добрый день',
            'hello',
            'hi',
            'test',
            'тест',
            'пинг',
            'как дела',
        ];

        foreach ($smallTalk as $phrase) {
            if ($normalized === $phrase) {
                return true;
            }
        }

        return mb_strlen($normalized) <= 16 && preg_match('/^[\p{L}\p{N}\s\!\?\,\.\-]+$/u', $normalized) === 1;
    }

    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    protected function createLightweightChatFlow(AgentRun $run)
    {
        $step = AgentStep::query()->create([
            'run_id' => $run->id,
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'step_type' => 'chat_response',
            'name' => 'Chat response',
            'status' => AgentStepStatus::QUEUED->value,
            'is_risky' => false,
            'depends_on' => [],
            'input_payload' => [
                'goal' => $run->goal,
                'required_tool' => 'none',
            ],
            'artifact_contract' => [
                'required_sections' => ['summary'],
                'min_findings' => 0,
            ],
            'max_retries' => 0,
            'metadata' => [
                'source' => 'goal_driven_chat',
                'phase' => 'chat',
                'flow_type' => 'lightweight_chat',
            ],
        ]);

        $metadata = (array) $run->metadata;
        $metadata['flow_type'] = 'lightweight_chat';
        $metadata['goal_classification'] = 'lightweight_chat';
        $run->metadata = $metadata;
        $run->save();

        return collect([$step]);
    }

    protected function isSimpleContentChangeGoal(string $goal): bool
    {
        $goal = mb_strtolower(trim($goal));
        $hasUrl = preg_match('/https?:\/\/[^\s]+/iu', $goal) === 1;
        $isHomepageTarget = str_contains($goal, '136.119.84.22/')
            || str_contains($goal, 'http://136.119.84.22')
            || str_contains($goal, 'главн')
            || str_contains($goal, 'homepage')
            || str_contains($goal, 'home page');

        $contentIntent = str_contains($goal, 'слоган')
            || str_contains($goal, 'headline')
            || str_contains($goal, 'tagline')
            || str_contains($goal, 'текст')
            || str_contains($goal, 'копирайт')
            || str_contains($goal, 'замени')
            || str_contains($goal, 'обнови текст')
            || str_contains($goal, 'измени текст');

        $looksLikeDirectSlogan = $hasUrl
            && $isHomepageTarget
            && ! str_contains($goal, '/category/')
            && mb_strlen($goal) <= 220
            && preg_match('/https?:\/\/[^\s]+\s+.+/iu', $goal) === 1;

        return ($hasUrl && $isHomepageTarget && $contentIntent) || ($isHomepageTarget && $contentIntent) || $looksLikeDirectSlogan;
    }

    protected function isUiRedesignGoal(string $goal): bool
    {
        $goal = mb_strtolower($goal);

        $keywords = [
            'ui',
            'ux',
            'design',
            'redesign',
            'дизайн',
            'редизайн',
            'обнови',
            'доработай',
            'страничк',
            'page',
            'category/',
            'http://',
            'https://',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($goal, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    protected function createContentEditFlow(AgentRun $run)
    {
        $definitions = [
            [
                'step_type' => 'page_discovery',
                'name' => 'Page discovery',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'target_url', 'current_state', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'browser_audit',
                'name' => 'Browser audit',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'ui_issues', 'ux_issues', 'screenshots', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'content_redesign',
                'name' => 'Content redesign',
                'required_tool' => 'none',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'content_blocks', 'copy_updates', 'seo_updates', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'target_resolution',
                'name' => 'Target resolution',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'target_type', 'resolved_target', 'resolver_strategy', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'content_update_execution',
                'name' => 'Content update execution',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'applied_changes', 'changed_files', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'preview_capture',
                'name' => 'Preview capture',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'preview_urls', 'screenshots', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'before_after_evidence',
                'name' => 'Before/after evidence',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'before_state', 'after_state', 'diff_observations', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'validation',
                'name' => 'Validation',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'checks_performed', 'validation_result', 'traceability', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
        ];

        $created = collect();
        $prevId = null;

        foreach ($definitions as $definition) {
            $step = AgentStep::query()->create([
                'run_id' => $run->id,
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'step_type' => $definition['step_type'],
                'name' => $definition['name'],
                'status' => AgentStepStatus::QUEUED->value,
                'is_risky' => false,
                'depends_on' => $prevId ? [$prevId] : [],
                'input_payload' => [
                    'goal' => $run->goal,
                    'required_tool' => $definition['required_tool'],
                ],
                'artifact_contract' => $definition['artifact_contract'],
                'max_retries' => 1,
                'metadata' => [
                    'source' => 'goal_driven_content_edit',
                    'phase' => $definition['phase'],
                    'flow_type' => 'content_edit',
                ],
            ]);

            $created->push($step);
            $prevId = $step->id;
        }

        $metadata = (array) $run->metadata;
        $metadata['flow_type'] = 'content_edit';
        $run->metadata = $metadata;
        $run->save();

        return $created;
    }

    /**
     * @return \Illuminate\Support\Collection<int, AgentStep>
     */
    protected function createUiRedesignFlow(AgentRun $run)
    {
        $definitions = [
            [
                'step_type' => 'page_discovery',
                'name' => 'Page discovery',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'target_url', 'current_state', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'browser_audit',
                'name' => 'Browser audit',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'ui_issues', 'ux_issues', 'screenshots', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'research_benchmark',
                'name' => 'Research benchmark',
                'required_tool' => 'research',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'benchmarks', 'best_practices', 'recommendations', 'evidence', 'confidence_level'],
                    'min_findings' => 1,
                ],
            ],
            [
                'step_type' => 'content_redesign',
                'name' => 'Content redesign',
                'required_tool' => 'research',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'content_blocks', 'copy_updates', 'seo_updates', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'image_generation',
                'name' => 'Image generation',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'updated_assets', 'prompts', 'optimization', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'implementation_patch_plan',
                'name' => 'Implementation patch plan',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'changed_files', 'patch_strategy', 'validation_steps', 'rollback_notes', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'template_or_code_patch_execution',
                'name' => 'Template or code patch execution',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'applied_changes', 'patch_diff', 'changed_files', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'preview_capture',
                'name' => 'Preview capture',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'preview_urls', 'screenshots', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'before_after_evidence',
                'name' => 'Before/after evidence',
                'required_tool' => 'browser',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'before_state', 'after_state', 'diff_observations', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'quality_validation_bundle',
                'name' => 'Quality validation bundle',
                'required_tool' => 'code',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'test_plan', 'test_results', 'accessibility_checks', 'performance_checks', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
            [
                'step_type' => 'final_delivery_package',
                'name' => 'Final delivery package',
                'required_tool' => 'none',
                'phase' => 'delivery',
                'artifact_contract' => [
                    'required_sections' => ['summary', 'delivery_scope', 'applied_changes', 'before_after_evidence', 'validation_rerun', 'traceability', 'evidence', 'confidence_level'],
                    'min_findings' => 0,
                ],
            ],
        ];

        $created = collect();
        $prevId = null;

        foreach ($definitions as $definition) {
            $step = AgentStep::query()->create([
                'run_id' => $run->id,
                'organization_id' => $run->organization_id,
                'tenant_id' => $run->tenant_id,
                'step_type' => $definition['step_type'],
                'name' => $definition['name'],
                'status' => AgentStepStatus::QUEUED->value,
                'is_risky' => false,
                'depends_on' => $prevId ? [$prevId] : [],
                'input_payload' => [
                    'goal' => $run->goal,
                    'required_tool' => $definition['required_tool'],
                ],
                'artifact_contract' => $definition['artifact_contract'],
                'max_retries' => 1,
                'metadata' => [
                    'source' => 'goal_driven_ui_redesign',
                    'phase' => $definition['phase'],
                    'flow_type' => 'ui_redesign',
                ],
            ]);

            $created->push($step);
            $prevId = $step->id;
        }

        $metadata = (array) $run->metadata;
        $metadata['flow_type'] = 'ui_redesign';
        $run->metadata = $metadata;
        $run->save();

        return $created;
    }
}
