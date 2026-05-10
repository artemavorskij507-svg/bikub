<?php

namespace Tests\Feature\Ops\Dispatch;

use App\Domain\Moving\Models\TeamAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class MovingTeamEtaAggregationTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_moving_team_eta_uses_max_member_eta(): void
    {
        $e1 = $this->createExecutor([
            'name' => 'Moving A',
            'display_name' => 'Moving A',
        ]);
        $e2 = $this->createExecutor([
            'name' => 'Moving B',
            'display_name' => 'Moving B',
        ]);
        $this->createShift($e1);
        $this->createShift($e2);

        $this->mockRedis([
            "executor:{$e1->id}:last_location" => json_encode(['latitude' => 50.4501, 'longitude' => 30.5234]),
            "executor:{$e2->id}:last_location" => json_encode(['latitude' => 50.5501, 'longitude' => 30.6234]),
        ]);

        $job = $this->createServiceJob([
            'service_domain' => 'moving',
            'job_kind' => 'smoke-moving-team',
            'required_team_size' => 2,
        ]);

        $run = $this->runDispatchForJob($job);
        $team = TeamAssignment::query()->where('service_job_id', $job->id)->latest('id')->first();

        $this->assertSame('completed', $run->status);
        $this->assertNotNull($team);
        $this->assertCount(2, (array) $team->member_executor_ids_json);

        $memberEtas = (array) data_get($team->metadata, 'member_etas', []);
        $this->assertCount(2, $memberEtas);

        $maxEta = max(array_map(static fn (array $item): int => (int) ($item['eta_seconds'] ?? 0), $memberEtas));
        $deltaSeconds = abs(now()->diffInSeconds($team->eta_at, false) - $maxEta);

        $this->assertLessThanOrEqual(5, $deltaSeconds);
    }
}

