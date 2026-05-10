<?php

namespace Tests\Feature\Admin\Repair;

use App\Models\Order;
use App\Models\RepairProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Создаем админа
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin'); // Предполагаем, что есть такая роль
    }

    public function test_admin_can_view_repair_project_in_filament(): void
    {
        $order = Order::factory()->create();
        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
            'title' => 'Test Project',
            'status' => 'assessment',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/repair-projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Project');
    }

    public function test_admin_can_list_repair_projects(): void
    {
        RepairProject::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/repair-projects');

        $response->assertStatus(200);
    }
}
