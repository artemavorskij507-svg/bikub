<?php

namespace Tests\Feature\Ops\Workbench;

use App\Domain\Exceptions\Models\OperationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class ExceptionAcknowledgeIdempotencyTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_exception_acknowledge_replay_returns_cached_success(): void
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
            'type' => 'smoke_ack',
            'exception_type' => 'smoke_ack',
            'severity' => 'medium',
            'status' => 'open',
            'detected_by' => 'system',
            'detected_at' => now(),
            'payload' => ['smoke' => true],
        ]);

        $payload = [
            'expected_exception_version' => optional($exception->updated_at)->format('Y-m-d H:i:s.u'),
        ];
        $idemKey = 'test-ex-ack-001';

        $first = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/exceptions/{$exception->id}/acknowledge", $payload);
        $second = $this->withHeader('X-Idempotency-Key', $idemKey)
            ->postJson("/api/ops/exceptions/{$exception->id}/acknowledge", $payload);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json(), $second->json());
        $this->assertSame('acknowledged', $exception->fresh()->status);
    }
}

