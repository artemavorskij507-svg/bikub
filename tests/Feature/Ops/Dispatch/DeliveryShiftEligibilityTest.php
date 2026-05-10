<?php

namespace Tests\Feature\Ops\Dispatch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class DeliveryShiftEligibilityTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_delivery_candidate_is_rejected_when_out_of_shift(): void
    {
        $this->mockRedis();

        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'smoke-delivery-shift',
        ]);

        $executor = $this->createExecutor();
        $this->createShift($executor, [
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->subHours(2),
            'start_time' => now()->subHours(4)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
        ]);

        $run = $this->runDispatchForJob($job);
        $candidate = $this->latestCandidateForJob($job);

        $this->assertSame('no_candidate', $run->status);
        $this->assertFalse((bool) $candidate->eligible);
        $this->assertContains('after_shift_end', (array) $candidate->ineligibility_reasons);
        $this->assertFalse((bool) data_get($candidate->score_breakdown, 'shift.eligible', true));
    }
}

