<?php

namespace Tests\Feature\Ops\Workbench;

use App\Domain\Exceptions\Models\OperationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class ExceptionResolveIdempotencyTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_exception_resolve_replay_is_idempotent_and_conflicts_for_different_payload(): void
    {
        $this->mockRedis();
        $this->actingAsOpsAdmin();

        $job = $this->createServiceJob(['status' => 'assigned']);
        $executor = $this->createExecutor();
        $this->createShift($executor);

        $exception = OperationException::query()->create([
            'organization_id' => $job->organization_id,
            'tenant_id' => $job->tenant_id,
            'service_job_id' => $job->id,
            'assignment_id' => null,
            'executor_id' => $executor->id,
            'type' => 'smoke_resolve',
            'exception_type' => 'smoke_resolve',
            'severity' => 'high',
            'status' => 'open',
            'detected_by' => 'system',
            'detected_at' => now(),
            'payload' => ['smoke' => true],
        ]);

        $idemKey = 'test-ex-res-001';
        $payload = [
            'expected_exception_version' => optional($exception->updated_at)->format('Y-m-d H:i:s.u'),
            'resolution_code' => 'resolved_by_dispatcher',
            'resolution_notes' => 'resolved in smoke',
            'root_cause' => 'test',
        ];

        $first = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/exceptions/{$exception->id}/resolve-workbench", $payload);
        $second = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/exceptions/{$exception->id}/resolve-workbench", $payload);
        $third = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/exceptions/{$exception->id}/resolve-workbench", [
                'expected_exception_version' => optional($exception->fresh()->updated_at)->format('Y-m-d H:i:s.u'),
                'resolution_code' => 'different_code',
                'resolution_notes' => 'different payload',
                'root_cause' => 'test',
            ]);

        $first->assertOk();
        $second->assertOk();
        $third->assertStatus(409);
        $this->assertSame($first->json(), $second->json());
        $this->assertSame('resolved', $exception->fresh()->status);
    }
}

