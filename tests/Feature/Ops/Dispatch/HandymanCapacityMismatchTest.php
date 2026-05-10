<?php

namespace Tests\Feature\Ops\Dispatch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class HandymanCapacityMismatchTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_handyman_candidate_is_rejected_when_required_equipment_missing(): void
    {
        $this->mockRedis();

        $job = $this->createServiceJob([
            'service_domain' => 'handyman',
            'job_kind' => 'smoke-handyman-capacity',
            'required_equipment' => ['pipe_wrench'],
            'required_skills' => ['plumbing'],
        ]);

        $executor = $this->createExecutor([
            'equipment' => ['drill'],
            'skills' => ['electricity'],
            'capabilities' => [],
        ]);
        $this->createShift($executor);

        $run = $this->runDispatchForJob($job);
        $candidate = $this->latestCandidateForJob($job);

        $this->assertSame('no_candidate', $run->status);
        $this->assertFalse((bool) $candidate->eligible);
        $this->assertNotEmpty((array) $candidate->ineligibility_reasons);
        $this->assertStringContainsString(
            'missing_equipment:pipe_wrench',
            implode(',', (array) $candidate->ineligibility_reasons)
        );
        $this->assertFalse((bool) data_get($candidate->score_breakdown, 'capacity.fits', true));
    }
}

