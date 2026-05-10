<?php

namespace Tests\Feature\AgentOS;

use App\Domain\AgentOS\Actions\StartAgentRunAction;
use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Services\RunOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentOsContinuousLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_audit_run_completes_without_manual_push(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.tool_fallback.enabled', true);
        config()->set('agent-os.audit.auto_followup_on_findings', true);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'audit project',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000123',
            'tenant_id' => 1,
            'idempotency_key' => 'audit-loop-1',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);

        $this->assertSame(AgentRunStatus::COMPLETED->value, $run->status);
        $this->assertTrue((bool) data_get($run->metadata, 'followup_phase_created', false));
        $this->assertGreaterThan(0, (int) data_get($run->metadata, 'audit_findings_count', 0));
        $this->assertDatabaseHas('agent_artifacts', [
            'run_id' => $run->id,
            'artifact_type' => 'final_delivery_package',
        ]);
    }

    public function test_loop_auto_dispatches_next_ready_steps(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.tool_fallback.enabled', true);
        config()->set('agent-os.audit.auto_followup_on_findings', true);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'audit project',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000124',
            'tenant_id' => 1,
            'idempotency_key' => 'audit-loop-2',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);
        $steps = $run->fresh()->steps()->orderBy('id')->get();

        $this->assertGreaterThan(6, $steps->count());
        $this->assertTrue($steps->every(fn ($step) => $step->status === AgentStepStatus::COMPLETED->value));
    }

    public function test_risky_step_stops_run_in_ready_for_review(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.tool_fallback.enabled', true);

        $run = AgentRun::query()->create([
            'goal' => 'risk check',
            'risk_level' => 'high',
            'status' => AgentRunStatus::PLANNING->value,
            'organization_id' => '00000000-0000-0000-0000-000000000125',
            'tenant_id' => 1,
        ]);

        $run->steps()->create([
            'organization_id' => $run->organization_id,
            'tenant_id' => $run->tenant_id,
            'step_type' => 'security_review',
            'name' => 'Risky security review',
            'status' => AgentStepStatus::QUEUED->value,
            'is_risky' => true,
            'depends_on' => [],
            'input_payload' => ['required_tool' => 'research'],
            'artifact_contract' => ['required' => ['summary']],
            'max_retries' => 0,
        ]);

        $run = app(RunOrchestratorService::class)->run($run);

        $this->assertSame(AgentRunStatus::READY_FOR_REVIEW->value, $run->status);
    }

    public function test_unavailable_tool_degrades_with_reduced_confidence_when_fallback_enabled(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.feature_flags.tool_code', false);
        config()->set('agent-os.tool_fallback.enabled', true);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'audit project',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000126',
            'tenant_id' => 1,
            'idempotency_key' => 'audit-loop-4',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);
        $step = $run->fresh()->steps()->where('step_type', 'testing_cicd_review')->first();

        $this->assertNotNull($step);
        $this->assertTrue((bool) $step->reduced_confidence);
        $this->assertNotEmpty($step->confidence_reason);
    }

    public function test_unavailable_tool_blocks_when_fallback_disabled(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.feature_flags.tool_research', false);
        config()->set('agent-os.tool_fallback.enabled', false);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'audit project',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000127',
            'tenant_id' => 1,
            'idempotency_key' => 'audit-loop-5',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);

        $this->assertSame(AgentRunStatus::BLOCKED->value, $run->status);
    }

    public function test_followup_required_when_findings_exist_and_auto_followup_disabled(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.tool_fallback.enabled', true);
        config()->set('agent-os.audit.auto_followup_on_findings', false);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'audit project',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000128',
            'tenant_id' => 1,
            'idempotency_key' => 'audit-loop-followup-required',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);

        $this->assertSame(AgentRunStatus::FOLLOWUP_REQUIRED->value, $run->status);
        $this->assertGreaterThan(0, (int) data_get($run->metadata, 'audit_findings_count', 0));
    }

    public function test_ui_redesign_goal_uses_goal_driven_flow_and_final_report_has_no_na_placeholders(): void
    {
        config()->set('agent-os.execution_mode', 'sync');
        config()->set('agent-os.tool_fallback.enabled', true);

        $run = app(StartAgentRunAction::class)->execute([
            'goal' => 'доработай http://136.119.84.22/category/food и обнови дизайн страницы',
            'risk_level' => 'medium',
            'organization_id' => '00000000-0000-0000-0000-000000000129',
            'tenant_id' => 1,
            'idempotency_key' => 'ui-redesign-flow-1',
        ]);

        $run = app(RunOrchestratorService::class)->run($run);
        $stepTypes = $run->fresh()->steps()->pluck('step_type')->all();

        $this->assertContains('page_discovery', $stepTypes);
        $this->assertContains('ui_ux_redesign_spec', $stepTypes);
        $this->assertContains('implementation_patch_plan', $stepTypes);
        $this->assertContains('quality_validation_bundle', $stepTypes);
        $this->assertSame(AgentRunStatus::COMPLETED->value, $run->status);

        $final = $run->fresh()->artifacts()
            ->where('artifact_type', 'final_delivery_package')
            ->latest('id')
            ->first();

        $this->assertNotNull($final);
        $this->assertStringNotContainsString('N/A', (string) $final->content);
    }
}
