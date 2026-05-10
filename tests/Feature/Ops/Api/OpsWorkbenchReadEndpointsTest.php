<?php

namespace Tests\Feature\Ops\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class OpsWorkbenchReadEndpointsTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_workbench_read_endpoints_return_expected_payloads(): void
    {
        $this->mockRedis();
        $this->actingAsOpsAdmin();

        $executor = $this->createExecutor();
        $this->createShift($executor);
        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'api-read',
        ]);
        $this->runDispatchForJob($job);

        $map = $this->getJson('/api/ops/map/live');
        $drawer = $this->getJson("/api/ops/jobs/{$job->id}/drawer");
        $compare = $this->getJson("/api/ops/jobs/{$job->id}/candidate-compare");
        $triage = $this->getJson('/api/ops/workbench/triage');
        $saved = $this->getJson('/api/ops/workbench/saved-filters');
        $routingMetrics = $this->getJson('/api/ops/workbench/routing-shadow-metrics?days=3');
        $routingHealth = $this->getJson('/api/ops/workbench/routing-provider-health');

        $map->assertOk()->assertJsonStructure(['summary', 'jobs', 'executors', 'exceptions']);
        $drawer->assertOk()->assertJsonPath('job.id', $job->id);
        $compare->assertOk()->assertJsonPath('job_id', $job->id);
        $triage->assertOk()->assertJsonStructure(['cards']);
        $saved->assertOk()->assertJsonStructure(['filters']);
        $routingMetrics->assertOk()->assertJsonStructure(['provider_health', 'metrics']);
        $routingHealth->assertOk()->assertJsonStructure(['provider', 'reachable', 'checked_at']);
    }
}

