<?php

namespace Tests\Feature\Tasks;

use App\Models\Employee;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProofAndSlaGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create();
        $this->zone = GeoZone::factory()->create();
        $this->slot = ScheduleSlot::factory()->create();
        $this->order = Order::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_prevents_completing_task_without_proof_when_proof_required()
    {
        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'proof_required' => true,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
        ]);

        $this->expectException(ValidationException::class);

        $task->update([
            'status' => 'completed',
            // Missing proof attachment
        ]);
    }

    /** @test */
    public function it_allows_completing_task_with_proof_when_proof_required()
    {
        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'proof_required' => true,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
            'attachments' => [
                ['type' => 'photo', 'url' => 'https://example.com/proof.jpg', 'uploaded_at' => now()->toDateTimeString()],
            ],
        ]);

        $task->update(['status' => 'completed']);

        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertTrue($task->hasProof());
    }

    /** @test */
    public function it_allows_completing_task_without_proof_when_proof_not_required()
    {
        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'proof_required' => false,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
        ]);

        $task->update(['status' => 'completed']);

        $this->assertEquals('completed', $task->fresh()->status);
    }

    /** @test */
    public function it_signals_sla_at_risk_when_deadline_approaching()
    {
        $deadline = now()->addMinutes(15);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'sla_deadline_at' => $deadline,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
        ]);

        $this->assertTrue($task->isSlaAtRisk());
    }

    /** @test */
    public function it_signals_sla_critical_when_deadline_passed()
    {
        $deadline = now()->subMinutes(10);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'sla_deadline_at' => $deadline,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
        ]);

        $this->assertTrue($task->isSlaCritical());
    }

    /** @test */
    public function it_does_not_signal_sla_at_risk_when_deadline_far_away()
    {
        $deadline = now()->addHours(2);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'assignee_id' => $this->employee->id,
            'status' => 'in_progress',
            'sla_deadline_at' => $deadline,
            'zone_id' => $this->zone->id,
            'slot_id' => $this->slot->id,
        ]);

        $this->assertFalse($task->isSlaAtRisk());
    }
}
