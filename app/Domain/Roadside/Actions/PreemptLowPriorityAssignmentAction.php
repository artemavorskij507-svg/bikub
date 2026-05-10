<?php

namespace App\Domain\Roadside\Actions;

use App\Domain\Dispatch\Enums\AssignmentStatus;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Models\Operations\Assignment;

class PreemptLowPriorityAssignmentAction
{
    public function __construct(
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
    ) {}

    public function execute(Assignment $assignment, int $emergencyJobId, ?int $actorUserId = null): Assignment
    {
        if (! in_array((string) $assignment->status, [AssignmentStatus::PROPOSED->value, AssignmentStatus::ACCEPTED->value], true)) {
            return $assignment;
        }

        $assignment->update([
            'status' => AssignmentStatus::REASSIGNED->value,
            'cancelled_at' => now(),
            'cancel_reason' => 'roadside_emergency_preemption',
            'metadata' => array_merge((array) $assignment->metadata, [
                'preempted_by_job_id' => $emergencyJobId,
                'preempted_at' => now()->toIso8601String(),
            ]),
        ]);

        if ($assignment->serviceJob) {
            $this->writeJobTimelineAction->execute(
                job: $assignment->serviceJob,
                eventType: 'assignment_preempted_for_emergency',
                actorType: 'system',
                actorId: $actorUserId,
                assignmentId: $assignment->id,
                payload: [
                    'preempted_assignment_id' => $assignment->id,
                    'executor_id' => $assignment->executor_id,
                    'emergency_job_id' => $emergencyJobId,
                ],
            );
        }

        return $assignment->fresh();
    }
}

