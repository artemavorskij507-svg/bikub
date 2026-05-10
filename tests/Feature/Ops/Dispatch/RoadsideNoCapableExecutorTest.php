<?php

namespace Tests\Feature\Ops\Dispatch;

use App\Domain\Exceptions\Models\OperationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class RoadsideNoCapableExecutorTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_roadside_job_without_capable_executor_ends_with_no_candidate_and_exception(): void
    {
        $this->mockRedis();

        $executor = $this->createExecutor([
            'skills' => ['diagnostics'],
            'capabilities' => ['diagnostics'],
            'equipment' => [],
        ]);
        $this->createShift($executor);

        $job = $this->createServiceJob([
            'service_domain' => 'roadside',
            'job_kind' => 'smoke-roadside-no-capable',
            'priority' => 'emergency',
            'required_skills' => ['tow'],
            'required_equipment' => ['tow_truck'],
        ]);

        $run = $this->runDispatchForJob($job);
        $candidate = $this->latestCandidateForJob($job);
        $exception = OperationException::query()
            ->where('service_job_id', $job->id)
            ->where(function ($q): void {
                $q->where('type', 'no_executor_found')
                    ->orWhere('exception_type', 'no_executor_found');
            })
            ->first();

        $this->assertSame('no_candidate', $run->status);
        $this->assertFalse((bool) $candidate->eligible);
        $this->assertStringContainsString('missing_', implode(',', (array) $candidate->ineligibility_reasons));
        $this->assertNotNull($exception);
        $this->assertSame('open', $exception->status);
    }
}

