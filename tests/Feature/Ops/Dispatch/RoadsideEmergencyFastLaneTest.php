<?php

namespace Tests\Feature\Ops\Dispatch;

use App\Domain\Dispatch\Models\Assignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class RoadsideEmergencyFastLaneTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_roadside_emergency_preempts_low_priority_and_sets_short_acceptance_deadline(): void
    {
        $this->mockRedis();

        $executor = $this->createExecutor([
            'skills' => ['tow'],
            'capabilities' => ['tow'],
        ]);
        $this->createShift($executor);

        $lowPriorityJob = $this->createServiceJob([
            'service_domain' => 'roadside',
            'job_kind' => 'smoke-roadside-low',
            'priority' => 'normal',
            'status' => 'assigned',
        ]);
        $preemptible = Assignment::query()->create([
            'organization_id' => $lowPriorityJob->organization_id,
            'tenant_id' => $lowPriorityJob->tenant_id,
            'service_job_id' => $lowPriorityJob->id,
            'executor_id' => $executor->id,
            'status' => 'proposed',
            'assignment_mode' => 'auto_assign',
        ]);
        $lowPriorityJob->update([
            'executor_id' => $executor->id,
            'assignment_id' => $preemptible->id,
        ]);

        $emergencyJob = $this->createServiceJob([
            'service_domain' => 'roadside',
            'job_kind' => 'smoke-roadside-emergency',
            'priority' => 'emergency',
            'required_skills' => ['tow'],
        ]);

        $run = $this->runDispatchForJob($emergencyJob);
        $newAssignment = $this->latestAssignmentForJob($emergencyJob);
        $preemptible->refresh();

        $this->assertSame('completed', $run->status);
        $this->assertSame('assigned', $emergencyJob->fresh()->status);
        $this->assertSame('reassigned', $preemptible->status);
        $this->assertSame('roadside_emergency_preemption', $preemptible->cancel_reason);
        $this->assertNotNull($newAssignment->acceptance_deadline_at);
        $this->assertSame(120, (int) $newAssignment->acceptance_timeout_seconds);
    }
}

