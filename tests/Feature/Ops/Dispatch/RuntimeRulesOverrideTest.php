<?php

namespace Tests\Feature\Ops\Dispatch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class RuntimeRulesOverrideTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_runtime_dispatch_rules_override_is_reflected_in_candidate_breakdown(): void
    {
        $this->mockRedis();

        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'smoke-runtime-overrides',
        ]);

        $executor = $this->createExecutor();
        $this->createShift($executor);

        $this->seedRuleOverride($job, 'weights.eta', 0.55);
        $this->seedRuleOverride($job, 'modifiers.window_high_risk_penalty', -22);

        $this->runDispatchForJob($job);
        $candidate = $this->latestCandidateForJob($job);

        $this->assertSame(0.55, (float) data_get($candidate->score_breakdown, 'weighted.weights.eta'));
        $this->assertSame(
            -22.0,
            (float) data_get($candidate->score_breakdown, 'modifiers.time_window_risk_penalty.modifier', 0)
        );
    }
}

