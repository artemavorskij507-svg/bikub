<?php

namespace App\Domain\Tracking\Jobs;

use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectStalledAssignmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $assignedButNotStartedAfterMinutes = 10,
        public int $arrivedButNotStartedWorkAfterMinutes = 15,
    ) {}

    public function handle(OpenOperationExceptionAction $openOperationExceptionAction): void
    {
        Assignment::query()
            ->where('status', 'accepted')
            ->where('accepted_at', '<=', now()->subMinutes($this->assignedButNotStartedAfterMinutes))
            ->with('serviceJob')
            ->chunkById(100, function ($assignments) use ($openOperationExceptionAction): void {
                foreach ($assignments as $assignment) {
                    if (! $assignment->serviceJob) {
                        continue;
                    }

                    $openOperationExceptionAction->execute(
                        job: $assignment->serviceJob,
                        type: 'assignment_stalled',
                        severity: 'medium',
                        assignmentId: $assignment->id,
                        executorId: $assignment->executor_id,
                        detectedBy: 'system',
                        payload: [
                            'accepted_at' => optional($assignment->accepted_at)->toIso8601String(),
                            'threshold_minutes' => $this->assignedButNotStartedAfterMinutes,
                        ],
                    );
                }
            });

        Assignment::query()
            ->whereNotNull('arrived_at')
            ->whereNull('completed_at')
            ->where('arrived_at', '<=', now()->subMinutes($this->arrivedButNotStartedWorkAfterMinutes))
            ->with('serviceJob')
            ->chunkById(100, function ($assignments) use ($openOperationExceptionAction): void {
                foreach ($assignments as $assignment) {
                    if (! $assignment->serviceJob) {
                        continue;
                    }

                    if ($assignment->serviceJob->status === 'in_progress') {
                        continue;
                    }

                    $openOperationExceptionAction->execute(
                        job: $assignment->serviceJob,
                        type: 'work_not_started_after_arrival',
                        severity: 'medium',
                        assignmentId: $assignment->id,
                        executorId: $assignment->executor_id,
                        detectedBy: 'system',
                        payload: [
                            'arrived_at' => optional($assignment->arrived_at)->toIso8601String(),
                            'threshold_minutes' => $this->arrivedButNotStartedWorkAfterMinutes,
                        ],
                    );
                }
            });
    }
}

