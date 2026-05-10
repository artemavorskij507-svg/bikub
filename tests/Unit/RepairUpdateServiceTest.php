<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\RepairMedia;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\User;
use App\Services\Repair\RepairUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_status_update_persists_record_and_updates_project_progress(): void
    {
        $order = Order::factory()->create();
        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
            'status' => 'in_progress',
        ]);
        $stage = RepairStage::factory()->create([
            'repair_project_id' => $project->id,
        ]);
        $author = User::factory()->create();

        $service = new RepairUpdateService;

        $update = $service->createStatusUpdate(
            $project,
            $stage,
            $author->id,
            'Промежуточный отчёт',
            'Работы идут по плану',
            45,
            'in_progress'
        );

        $this->assertDatabaseHas('repair_updates', [
            'id' => $update->id,
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage->id,
            'author_user_id' => $author->id,
            'progress_percent' => 45,
            'status_snapshot' => 'in_progress',
        ]);

        $project->refresh();
        $this->assertEquals(45, $project->overall_progress_percent);
    }

    public function test_add_photo_creates_media_record_and_stores_file(): void
    {
        Storage::fake('public');

        $order = Order::factory()->create();
        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
        ]);
        $stage = RepairStage::factory()->create([
            'repair_project_id' => $project->id,
        ]);

        $service = new RepairUpdateService;

        $file = UploadedFile::fake()->image('progress.jpg', 600, 400);

        $media = $service->addPhoto(
            $project,
            $stage,
            null,
            $file,
            'during',
            'Монтаж перегородок'
        );

        $this->assertInstanceOf(RepairMedia::class, $media);

        Storage::disk('public')->assertExists($media->path);

        $this->assertDatabaseHas('repair_media', [
            'id' => $media->id,
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage->id,
            'role' => 'during',
            'caption' => 'Монтаж перегородок',
        ]);
    }
}
