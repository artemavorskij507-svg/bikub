<?php

namespace Tests\Feature;

use App\Domain\Operations\Actions\NormalizeOrderToServiceJobAction;
use App\Jobs\CalculateDispatchCandidatesJob;
use App\Models\Operations\Assignment;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1CanonicalCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_order_to_service_job_is_idempotent(): void
    {
        $order = Order::factory()->create([
            'service_type' => 'delivery',
            'metadata' => [],
        ]);

        $action = app(NormalizeOrderToServiceJobAction::class);
        $job1 = $action->execute($order);
        $job2 = $action->execute($order);

        $this->assertSame($job1->id, $job2->id);
        $this->assertEquals(1, ServiceJob::query()
            ->where('source_type', 'order')
            ->where('source_id', $order->id)
            ->count());
    }

    public function test_calculate_dispatch_candidates_creates_assignment_and_updates_service_job(): void
    {
        $order = Order::factory()->create([
            'service_type' => 'delivery',
            'org_id' => null,
            'metadata' => [],
        ]);
        $job = app(NormalizeOrderToServiceJobAction::class)->execute($order);

        $user = User::factory()->create();
        Executor::create([
            'organization_id' => $job->organization_id,
            'user_id' => $user->id,
            'name' => 'Executor One',
            'display_name' => 'Executor One',
            'executor_type' => 'employee',
            'status' => 'available',
            'is_dispatchable' => true,
        ]);

        app()->call([new CalculateDispatchCandidatesJob($job->id), 'handle']);

        $this->assertDatabaseHas('assignments', [
            'service_job_id' => $job->id,
            'status' => 'proposed',
        ]);

        $this->assertTrue(Assignment::query()->where('service_job_id', $job->id)->exists());
        $this->assertEquals('assigned', $job->fresh()->status);
    }
}
