<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepairProjectDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_pm_can_see_project_dashboard_in_filament(): void
    {
        $order = Order::factory()->create();
        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
            'title' => 'Лофт на Невском',
            'overall_progress_percent' => 55,
        ]);

        $stage = RepairStage::factory()->create([
            'repair_project_id' => $project->id,
            'name' => 'Отделочные работы',
            'sequence' => 20,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/repair-projects/{$project->id}/dashboard");

        $response->assertStatus(200);
        $response->assertSee('Лофт на Невском');
        $response->assertSee('Отделочные работы');
        $response->assertSee('55%');
    }
}
