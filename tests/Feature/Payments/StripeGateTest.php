<?php

namespace Tests\Feature\Payments;

use App\Models\Employee;
use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripeGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create();
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_allows_task_assignment_when_payment_captured()
    {
        $this->order->update(['payment_status' => 'paid']);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'queued',
        ]);

        $task->update([
            'assignee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);

        $this->assertEquals('assigned', $task->fresh()->status);
        $this->assertEquals($this->employee->id, $task->fresh()->assignee_id);
    }

    /** @test */
    public function it_prevents_task_assignment_when_strict_payment_gate_enabled_and_payment_not_captured()
    {
        // Enable strict payment gate feature flag
        Config::set('feature_flags.strict_payment_gate', true);

        $this->order->update(['payment_status' => 'pending']);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'queued',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $task->update([
            'assignee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);
    }

    /** @test */
    public function it_allows_task_assignment_when_strict_payment_gate_disabled()
    {
        // Disable strict payment gate feature flag
        Config::set('feature_flags.strict_payment_gate', false);

        $this->order->update(['payment_status' => 'pending']);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'queued',
        ]);

        $task->update([
            'assignee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);

        $this->assertEquals('assigned', $task->fresh()->status);
    }

    /** @test */
    public function it_prevents_task_assignment_when_payment_failed()
    {
        Config::set('feature_flags.strict_payment_gate', true);

        $this->order->update(['payment_status' => 'failed']);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'queued',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $task->update([
            'assignee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);
    }

    /** @test */
    public function it_allows_task_assignment_when_payment_refunded_but_strict_gate_disabled()
    {
        Config::set('feature_flags.strict_payment_gate', false);

        $this->order->update(['payment_status' => 'refunded']);

        $task = Task::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'queued',
        ]);

        // Should still allow assignment if strict gate is disabled
        $task->update([
            'assignee_id' => $this->employee->id,
            'status' => 'assigned',
        ]);

        $this->assertEquals('assigned', $task->fresh()->status);
    }
}
