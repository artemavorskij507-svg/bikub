<?php

namespace Tests\Feature\Api\Executor;

use App\Models\HandymanAssignment;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Moving\ExecutorProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExecutorJobsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executor = User::factory()->create();
        $this->executorProfile = ExecutorProfile::factory()->create([
            'user_id' => $this->executor->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs($this->executor);
    }

    public function test_executor_can_see_his_assignments_list(): void
    {
        $order = Order::factory()->create();
        $service = HandymanService::factory()->create();
        $details = HandymanOrderDetails::factory()->for($order)->for($service)->create();

        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'proposed',
        ]);

        $response = $this->getJson('/api/v1/executor/jobs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'status',
                    'order',
                    'details',
                ],
            ],
        ]);
    }

    public function test_executor_cannot_see_foreign_assignment(): void
    {
        $otherExecutor = User::factory()->create();
        $otherProfile = ExecutorProfile::factory()->create([
            'user_id' => $otherExecutor->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $otherProfile->id,
        ]);

        $response = $this->getJson("/api/v1/executor/jobs/{$assignment->id}");

        $response->assertStatus(403);
    }

    public function test_executor_can_accept_proposed_assignment(): void
    {
        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'proposed',
        ]);

        $response = $this->postJson("/api/v1/executor/jobs/{$assignment->id}/accept");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $assignment->refresh();
        $this->assertEquals('accepted', $assignment->status);
        $this->assertTrue($assignment->is_primary);
    }

    public function test_executor_can_change_status_flow_to_finished(): void
    {
        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'accepted',
        ]);

        // Статус "started"
        $response = $this->postJson("/api/v1/executor/jobs/{$assignment->id}/status", [
            'status' => 'started',
        ]);
        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertNotNull($assignment->actual_start_at);

        // Статус "finished"
        $initialCount = $this->executorProfile->completed_orders_count;
        $response = $this->postJson("/api/v1/executor/jobs/{$assignment->id}/status", [
            'status' => 'finished',
        ]);
        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertEquals('completed', $assignment->status);
        $this->assertNotNull($assignment->actual_finish_at);

        $this->executorProfile->refresh();
        $this->assertEquals($initialCount + 1, $this->executorProfile->completed_orders_count);
    }

    public function test_executor_can_add_materials_entry(): void
    {
        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'accepted',
        ]);

        $response = $this->postJson("/api/v1/executor/jobs/{$assignment->id}/materials", [
            'description' => 'Крепеж',
            'quantity' => 10,
            'unit' => 'шт',
            'unit_price_minor' => 5000,
            'total_price_minor' => 50000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'material_id',
        ]);

        $this->assertDatabaseHas('handyman_materials_entries', [
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'description' => 'Крепеж',
        ]);
    }
}
