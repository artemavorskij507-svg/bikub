<?php

namespace Tests\Feature\AgentOS;

use App\Domain\AgentOS\Actions\CreateAgentRunAction;
use App\Domain\AgentOS\Actions\UpdateAgentRunStatusAction;
use App\Domain\AgentOS\Actions\UpdateAgentStepStatusAction;
use App\Domain\AgentOS\Enums\AgentRunStatus;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Jobs\DetectStaleAgentStepsJob;
use App\Domain\AgentOS\Models\AgentArtifact;
use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AgentOsSpecLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_enum_consistency_uses_canonical_step_statuses(): void
    {
        $expected = [
            'queued',
            'waiting_dependencies',
            'executing',
            'artifact_generated',
            'validation_failed',
            'needs_revision',
            'ready_for_review',
            'approved',
            'completed',
            'blocked',
            'failed',
        ];

        $actual = array_map(static fn (AgentStepStatus $status): string => $status->value, AgentStepStatus::cases());

        $this->assertSame($expected, $actual);
        $this->assertNotContains('validation_passed', $actual);
    }

    public function test_validation_path_transitions_work_as_specified(): void
    {
        $run = AgentRun::query()->create([
            'status' => AgentRunStatus::EXECUTING->value,
            'risk_level' => 'medium',
        ]);

        $step = AgentStep::query()->create([
            'run_id' => $run->id,
            'step_type' => 'code_patch',
            'status' => AgentStepStatus::EXECUTING->value,
        ]);

        $action = app(UpdateAgentStepStatusAction::class);

        $step = $action->execute($step, AgentStepStatus::ARTIFACT_GENERATED->value);
        $step = $action->execute($step, AgentStepStatus::VALIDATION_FAILED->value, ['validator_passed' => false]);
        $step = $action->execute($step, AgentStepStatus::NEEDS_REVISION->value);

        $this->assertSame(AgentStepStatus::NEEDS_REVISION->value, $step->status);

        $step = $action->execute($step, AgentStepStatus::QUEUED->value);
        $step = $action->execute($step, AgentStepStatus::EXECUTING->value);
        $step = $action->execute($step, AgentStepStatus::ARTIFACT_GENERATED->value);
        $step = $action->execute($step, AgentStepStatus::READY_FOR_REVIEW->value, ['validator_passed' => true]);

        $this->assertSame(AgentStepStatus::READY_FOR_REVIEW->value, $step->status);
    }

    public function test_risk_policy_for_high_and_critical_runs_enforces_approval_and_blocks_deploy(): void
    {
        $action = app(CreateAgentRunAction::class);

        $high = $action->execute([
            'organization_id' => '00000000-0000-0000-0000-000000000001',
            'tenant_id' => 10,
            'risk_level' => 'high',
        ]);

        $critical = $action->execute([
            'organization_id' => '00000000-0000-0000-0000-000000000001',
            'tenant_id' => 11,
            'risk_level' => 'critical',
        ]);

        $this->assertTrue($high->requires_approval);
        $this->assertFalse($high->deployment_allowed);
        $this->assertTrue($critical->requires_approval);
        $this->assertFalse($critical->deployment_allowed);
    }

    public function test_idempotency_key_reuses_active_run(): void
    {
        $action = app(CreateAgentRunAction::class);

        $payload = [
            'organization_id' => '00000000-0000-0000-0000-000000000010',
            'tenant_id' => 20,
            'risk_level' => 'medium',
            'idempotency_key' => 'same-key-1',
        ];

        $first = $action->execute($payload);
        $second = $action->execute($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('agent_runs', 1);
    }

    public function test_deploy_status_guard_blocks_when_feature_flags_are_disabled(): void
    {
        config()->set('agent-os.feature_flags.deploy_staging', false);
        config()->set('agent-os.feature_flags.deploy_production', false);

        $run = AgentRun::query()->create([
            'status' => AgentRunStatus::APPROVED->value,
            'risk_level' => 'medium',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(UpdateAgentRunStatusAction::class)->execute($run, AgentRunStatus::DEPLOY_READY->value);
    }

    public function test_stale_detection_marks_steps_blocked_or_failed_and_logs_artifact(): void
    {
        config()->set('agent-os.timeout.heartbeat_grace_minutes', 2);

        $run = AgentRun::query()->create([
            'status' => AgentRunStatus::EXECUTING->value,
            'risk_level' => 'medium',
        ]);

        $staleWithRetry = AgentStep::query()->create([
            'run_id' => $run->id,
            'step_type' => 'browser_audit',
            'status' => AgentStepStatus::EXECUTING->value,
            'retry_count' => 0,
            'max_retries' => 1,
            'heartbeat_at' => now()->subMinutes(5),
            'timeout_at' => now()->addMinutes(10),
        ]);

        $staleNoRetry = AgentStep::query()->create([
            'run_id' => $run->id,
            'step_type' => 'code_patch',
            'status' => AgentStepStatus::EXECUTING->value,
            'retry_count' => 1,
            'max_retries' => 1,
            'heartbeat_at' => now()->subMinutes(5),
            'timeout_at' => now()->subMinute(),
        ]);

        app(DetectStaleAgentStepsJob::class)->handle(app(UpdateAgentStepStatusAction::class));

        $this->assertSame(AgentStepStatus::BLOCKED->value, $staleWithRetry->fresh()->status);
        $this->assertSame(AgentStepStatus::FAILED->value, $staleNoRetry->fresh()->status);

        $this->assertGreaterThanOrEqual(2, AgentArtifact::query()->count());
    }

    public function test_retry_loop_needs_revision_to_queued_to_executing_works(): void
    {
        $run = AgentRun::query()->create([
            'status' => AgentRunStatus::EXECUTING->value,
            'risk_level' => 'medium',
        ]);

        $step = AgentStep::query()->create([
            'run_id' => $run->id,
            'step_type' => 'research',
            'status' => AgentStepStatus::NEEDS_REVISION->value,
        ]);

        $action = app(UpdateAgentStepStatusAction::class);
        $step = $action->execute($step, AgentStepStatus::QUEUED->value);
        $step = $action->execute($step, AgentStepStatus::EXECUTING->value);

        $this->assertSame(AgentStepStatus::EXECUTING->value, $step->status);
        $this->assertNotNull($step->started_at);
        $this->assertNotNull($step->heartbeat_at);
    }
}
