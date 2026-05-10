<?php

namespace Tests\Feature\Ops\Workbench;

use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class ManualDispatchIdempotencyTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_manual_dispatch_replay_with_same_key_is_idempotent(): void
    {
        $this->mockRedis();
        $this->actingAsOpsAdmin();

        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'status' => 'pending_dispatch',
        ]);
        $executor = $this->createExecutor();
        $this->createShift($executor);

        $payload = [
            'executor_id' => $executor->id,
            'expected_job_version' => $this->drawerVersion($job),
            'notes' => 'manual dispatch smoke',
        ];
        $idemKey = 'test-md-001';

        $first = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/jobs/{$job->id}/manual-dispatch", $payload);
        $second = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/jobs/{$job->id}/manual-dispatch", $payload);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('assignment_id'), $second->json('assignment_id'));
        $this->assertSame(1, (int) $job->assignments()->count());

        $record = WorkbenchIdempotencyKey::query()
            ->where('idempotency_key', $idemKey)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('completed', $record->state);
        $this->assertSame(200, (int) $record->response_status);
    }
}

