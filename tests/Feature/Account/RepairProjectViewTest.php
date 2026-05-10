<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\RepairMedia;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\RepairUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairProjectViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_repair_project_page(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => 'complex_repair',
        ]);

        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
            'title' => 'Квартира на Петровке',
            'status' => 'in_progress',
            'overall_progress_percent' => 35,
        ]);

        $stage = RepairStage::factory()->create([
            'repair_project_id' => $project->id,
            'name' => 'Черновые работы',
            'sequence' => 10,
            'status' => 'in_progress',
            'progress_percent' => 60,
        ]);

        $update = RepairUpdate::create([
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage->id,
            'author_user_id' => $user->id,
            'type' => 'status_change',
            'title' => 'Обновление статуса',
            'body' => 'Проведена разводка электрики.',
            'progress_percent' => 35,
            'status_snapshot' => 'in_progress',
        ]);

        $path = 'repairs/'.$project->id.'/demo.jpg';
        Storage::disk('public')->put($path, 'content');

        RepairMedia::create([
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage->id,
            'repair_update_id' => $update->id,
            'type' => 'photo',
            'role' => 'during',
            'disk' => 'public',
            'path' => $path,
            'thumbnail_path' => $path,
            'caption' => 'Монтаж стен',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->get(route('account.repairs.show', $project));

        $response->assertStatus(200);
        $response->assertSee('Квартира на Петровке');
        $response->assertSee('Черновые работы');
        $response->assertSee('Проведена разводка электрики.');
        $response->assertSee('Фото проекта');
    }

    public function test_user_cannot_view_foreign_repair_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'service_type' => 'complex_repair',
        ]);

        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
        ]);

        $response = $this->actingAs($intruder)
            ->withSession(['two_factor_passed_at' => now()])
            ->get(route('account.repairs.show', $project));

        $response->assertStatus(403);
    }
}
