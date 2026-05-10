<?php

namespace Tests\Feature\Ops\Dispatch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class DeliveryTimeWindowTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_delivery_candidate_is_rejected_on_time_window_miss(): void
    {
        $this->mockRedis();

        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'smoke-delivery-window',
            'time_window_end' => now()->addMinutes(5),
        ]);

        $executor = $this->createExecutor();
        $this->createShift($executor);

        $run = $this->runDispatchForJob($job);
        $candidate = $this->latestCandidateForJob($job);

        $this->assertSame('no_candidate', $run->status);
        $this->assertFalse((bool) $candidate->eligible);
        $this->assertContains('time_window_miss', (array) $candidate->ineligibility_reasons);
        $this->assertFalse((bool) data_get($candidate->score_breakdown, 'time_window.fits', true));
        $this->assertGreaterThan(0, (int) data_get($candidate->score_breakdown, 'time_window.lateness_seconds', 0));
    }
}

