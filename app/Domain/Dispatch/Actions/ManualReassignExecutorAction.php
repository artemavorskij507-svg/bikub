<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Enums\AssignmentStatus;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Events\ServiceJobBroadcasted;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Support\Facades\DB;

class ManualReassignExecutorAction
{
    public function __construct(
        private readonly ManualAssignExecutorAction $manualAssignExecutorAction,
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
        private readonly OpenOperationExceptionAction $openOperationExceptionAction,
        private readonly GuardJobMutableAction $guardJobMutableAction,
        private readonly GuardAssignmentMutableAction $guardAssignmentMutableAction,
    ) {}

    public function execute(ServiceJob $job, Executor $newExecutor, int $dispatcherUserId, ?string $reason = null): Assignment
    {
        $this->guardJobMutableAction->execute($job);

        return DB::transaction(function () use ($job, $newExecutor, $dispatcherUserId, $reason): Assignment {
            $previousAssignment = Assignment::query()
                ->where('service_job_id', $job->id)
                ->whereIn('status', [
                    AssignmentStatus::PROPOSED->value,
                    AssignmentStatus::OFFERED->value,
                    AssignmentStatus::ACCEPTED->value,
                    AssignmentStatus::ACTIVE->value,
                ])
                ->latest('id')
                ->first();

            $this->guardAssignmentMutableAction->execute($previousAssignment);

            if ($previousAssignment) {
                $previousAssignment->update([
                    'status' => AssignmentStatus::REASSIGNED->value,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason ?: 'manual_reassign',
                ]);
            }

            $newAssignment = $this->manualAssignExecutorAction->execute(
                job: $job->fresh(),
                executor: $newExecutor,
                dispatcherUserId: $dispatcherUserId,
                reason: $reason ?: 'manual_reassign',
            );

            $this->writeJobTimelineAction->execute(
                job: $job->fresh(),
                eventType: 'assignment_reassigned',
                actorType: 'dispatcher',
                actorId: $dispatcherUserId,
                assignmentId: $newAssignment->id,
                payload: [
                    'previous_assignment_id' => $previousAssignment?->id,
                    'previous_executor_id' => $previousAssignment?->executor_id,
                    'new_assignment_id' => $newAssignment->id,
                    'new_executor_id' => $newExecutor->id,
                    'reason' => $reason,
                ],
            );

            $this->openOperationExceptionAction->execute(
                job: $job->fresh(),
                type: 'manual_reassignment',
                severity: 'medium',
                assignmentId: $newAssignment->id,
                executorId: $newExecutor->id,
                detectedBy: 'dispatcher',
                payload: [
                    'reason' => $reason,
                    'previous_assignment_id' => $previousAssignment?->id,
                    'previous_executor_id' => $previousAssignment?->executor_id,
                    'new_assignment_id' => $newAssignment->id,
                    'new_executor_id' => $newExecutor->id,
                ],
            );

            event(new ServiceJobBroadcasted(
                organizationId: $job->organization_id,
                jobId: $job->id,
                payload: [
                    'status' => $job->fresh()->status,
                    'reason' => 'manual_reassign',
                    'executor_id' => $newExecutor->id,
                    'assignment_id' => $newAssignment->id,
                    'updated_at' => now()->toIso8601String(),
                ],
            ));

            return $newAssignment->fresh();
        });
    }
}
