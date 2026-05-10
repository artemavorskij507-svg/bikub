<?php

namespace Tests\Feature\Web\Executor;

use App\Models\HandymanAssignment;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Moving\ExecutorProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutorDashboardTest extends TestCase
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
    }

    public function test_executor_dashboard_requires_auth_and_executor_role(): void
    {
        // Без авторизации
        $response = $this->get('/executor');
        $response->assertRedirect('/login');

        // Обычный пользователь без executor profile
        $regularUser = User::factory()->create();
        $response = $this->actingAs($regularUser)->get('/executor');
        $response->assertStatus(403);
    }

    public function test_executor_sees_only_his_assignments(): void
    {
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();
        $service = HandymanService::factory()->create();

        HandymanOrderDetails::factory()->for($order1)->for($service)->create();
        HandymanOrderDetails::factory()->for($order2)->for($service)->create();

        $myAssignment = HandymanAssignment::factory()->create([
            'order_id' => $order1->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'proposed',
        ]);

        $otherExecutor = User::factory()->create();
        $otherProfile = ExecutorProfile::factory()->create([
            'user_id' => $otherExecutor->id,
            'is_active' => true,
        ]);

        $otherAssignment = HandymanAssignment::factory()->create([
            'order_id' => $order2->id,
            'executor_profile_id' => $otherProfile->id,
            'status' => 'proposed',
        ]);

        $response = $this->actingAs($this->executor)->get('/executor');

        $response->assertStatus(200);
        $response->assertSee('#'.$myAssignment->id);
        $response->assertDontSee('#'.$otherAssignment->id);
    }

    public function test_executor_can_accept_assignment_via_web(): void
    {
        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'proposed',
        ]);

        $response = $this->actingAs($this->executor)
            ->post("/executor/jobs/{$assignment->id}/accept");

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Задача принята.');

        $assignment->refresh();
        $this->assertEquals('accepted', $assignment->status);
    }

    public function test_executor_can_update_status_via_web(): void
    {
        $order = Order::factory()->create();
        $assignment = HandymanAssignment::factory()->create([
            'order_id' => $order->id,
            'executor_profile_id' => $this->executorProfile->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->executor)
            ->post("/executor/jobs/{$assignment->id}/status", [
                'status' => 'started',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Статус задачи обновлён.');

        $assignment->refresh();
        $this->assertNotNull($assignment->actual_start_at);
    }
}
