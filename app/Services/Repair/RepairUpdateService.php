<?php

namespace App\Services\Repair;

use App\Events\RepairUpdateCreated;
use App\Models\RepairMedia;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\RepairUpdate;
use Illuminate\Http\UploadedFile;

class RepairUpdateService
{
    public function createStatusUpdate(
        RepairProject $project,
        ?RepairStage $stage,
        ?int $authorUserId,
        string $title,
        ?string $body,
        ?int $progressPercent = null,
        ?string $statusSnapshot = null
    ): RepairUpdate {
        $update = RepairUpdate::create([
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage?->id,
            'author_user_id' => $authorUserId,
            'type' => 'status_change',
            'title' => $title,
            'body' => $body,
            'progress_percent' => $progressPercent,
            'status_snapshot' => $statusSnapshot ?? $project->status,
        ]);

        if ($progressPercent !== null) {
            $project->forceFill([
                'overall_progress_percent' => $progressPercent,
            ])->save();
        }

        event(new RepairUpdateCreated($update));

        return $update;
    }

    public function addPhoto(
        RepairProject $project,
        ?RepairStage $stage,
        ?RepairUpdate $update,
        UploadedFile $file,
        string $role = 'general',
        ?string $caption = null,
        string $disk = 'public'
    ): RepairMedia {
        $path = $file->store('repairs/'.$project->id, $disk);

        return RepairMedia::create([
            'repair_project_id' => $project->id,
            'repair_stage_id' => $stage?->id,
            'repair_update_id' => $update?->id,
            'type' => 'photo',
            'role' => $role,
            'disk' => $disk,
            'path' => $path,
            'thumbnail_path' => $path,
            'caption' => $caption,
        ]);
    }
}
