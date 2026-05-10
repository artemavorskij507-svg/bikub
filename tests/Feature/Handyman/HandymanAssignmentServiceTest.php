<?php

namespace Tests\Feature\Handyman;

use App\Models\ExecutorProfile;
use App\Models\HandymanAssignment;
use App\Models\HandymanOrderDetails;
use App\Models\Order;
use App\Models\User;
use App\Services\Handyman\HandymanAssignmentService;
use App\Services\Handyman\HandymanMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandymanAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HandymanAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HandymanAssignmentService(
            new HandymanMatchingService
        );
    }

    public function test_propose_assignments_creates_records_for_candidates(): void
    {
        $user = User::factory()->create();
        $executor1 = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
            'rating' => 5.0,
        ]);
        $executor2 = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
            'rating' => 4.5,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create(['order_id' => $order->id]);

        $assignments = $this->service->proposeAssignmentsForOrder($order);

        $this->assertGreaterThan(0, $assignments->count());
        $this->assertDatabaseHas('handyman_assignments', [
            'order_id' => $order->id,
            'executor_profile_id' => $executor1->id,
            'status' => 'proposed',
        ]);
        $this->assertDatabaseHas('handyman_assignments', [
            'order_id' => $order->id,
            'executor_profile_id' => $executor2->id,
            'status' => 'proposed',
        ]);
    }

    public function test_propose_assignments_returns_empty_when_no_details(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        // Нет HandymanOrderDetails

        $assignments = $this->service->proposeAssignmentsForOrder($order);

        $this->assertEmpty($assignments);
    }

    public function test_accept_assignment_marks_other_assignments_as_reassigned(): void
    {
        $user = User::factory()->create();
        $executor1 = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
        ]);
        $executor2 = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create(['order_id' => $order->id]);

        $assignments = $this->service->proposeAssignmentsForOrder($order);
        $firstAssignment = $assignments->first();

        $this->service->acceptAssignment($firstAssignment);

        $this->assertDatabaseHas('handyman_assignments', [
            'id' => $firstAssignment->id,
            'status' => 'accepted',
        ]);

        // Остальные назначения должны быть помечены как reassigned
        foreach ($assignments->skip(1) as $assignment) {
            $this->assertDatabaseHas('handyman_assignments', [
                'id' => $assignment->id,
                'status' => 'reassigned',
            ]);
        }
    }

    public function test_accept_assignment_updates_status(): void
    {
        $user = User::factory()->create();
        $executor = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $assignment = HandymanAssignment::create([
            'order_id' => $order->id,
            'executor_profile_id' => $executor->id,
            'status' => 'proposed',
            'is_primary' => true,
        ]);

        $this->service->acceptAssignment($assignment);

        $assignment->refresh();
        $this->assertEquals('accepted', $assignment->status);
    }
}
