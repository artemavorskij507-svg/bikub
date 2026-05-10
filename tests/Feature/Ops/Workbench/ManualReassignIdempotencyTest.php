<?php

namespace Tests\Feature\Ops\Workbench;

use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class ManualReassignIdempotencyTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_manual_reassign_replay_is_idempotent_and_conflicts_on_different_payload(): void
    {
        $this->mockRedis();
        $this->actingAsOpsAdmin();

        $from = $this->createExecutor();
        $to = $this->createExecutor();
        $this->createShift($from);
        $this->createShift($to);

        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'status' => 'assigned',
            'executor_id' => $from->id,
        ]);

        $existing = Assignment::query()->create([
            'organization_id' => $job->organization_id,
            'tenant_id' => $job->tenant_id,
            'service_job_id' => $job->id,
            'executor_id' => $from->id,
            'assignment_mode' => 'auto_assign',
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
        $job->update(['assignment_id' => $existing->id]);
        $job->refresh();

        $idemKey = 'test-mr-001';
        $payload = [
            'executor_id' => $to->id,
            'reason' => 'manual reassign smoke',
            'expected_job_version' => $this->drawerVersion($job),
        ];

        $first = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/jobs/{$job->id}/manual-reassign", $payload);
        $second = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/jobs/{$job->id}/manual-reassign", $payload);
        $third = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/jobs/{$job->id}/manual-reassign", [
                'executor_id' => $from->id,
                'reason' => 'different payload',
                'expected_job_version' => $this->drawerVersion($job->fresh()),
            ]);

        $first->assertOk();
        $second->assertOk();
        $third->assertStatus(409);

        $this->assertSame($first->json('assignment_id'), $second->json('assignment_id'));
        $this->assertSame(2, Assignment::query()->where('service_job_id', $job->id)->count());

        $record = WorkbenchIdempotencyKey::query()
            ->where('idempotency_key', $idemKey)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('completed', $record->state);
    }
}

