<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Enums\AssignmentStatus;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Dispatch\Actions\EnsureExecutorAssignableAction;
use App\Domain\Dispatch\Actions\GuardAssignmentMutableAction;
use App\Domain\Dispatch\Actions\GuardJobMutableAction;
use App\Domain\Operations\Actions\UpdateServiceJobStatusAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Enums\ServiceJobStatus;
use App\Domain\Operations\Events\ServiceJobBroadcasted;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Support\Facades\DB;

class ManualAssignExecutorAction
{
    public function __construct(
        private readonly UpdateServiceJobStatusAction $updateServiceJobStatusAction,
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
        private readonly GuardJobMutableAction $guardJobMutableAction,
        private readonly GuardAssignmentMutableAction $guardAssignmentMutableAction,
        private readonly EnsureExecutorAssignableAction $ensureExecutorAssignableAction,
    ) {}

    public function execute(ServiceJob $job, Executor $executor, int $dispatcherUserId, ?string $reason = null): Assignment
    {
        $this->guardJobMutableAction->execute($job);
        $this->guardAssignmentMutableAction->execute($job->currentAssignment);
        $this->ensureExecutorAssignableAction->execute($job, $executor);

        $existing = Assignment::query()
            ->where('service_job_id', $job->id)
            ->where('executor_id', $executor->id)
            ->whereIn('status', [
                AssignmentStatus::ACCEPTED->value,
                AssignmentStatus::ACTIVE->value,
            ])
            ->latest('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($job, $executor, $dispatcherUserId, $reason): Assignment {
            Assignment::query()
                ->where('service_job_id', $job->id)
                ->whereIn('status', [
                    AssignmentStatus::PROPOSED->value,
                    AssignmentStatus::OFFERED->value,
                    AssignmentStatus::ACCEPTED->value,
                    AssignmentStatus::ACTIVE->value,
                ])
                ->update([
                    'status' => AssignmentStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason ?: 'manual_override_replaced',
                ]);

            $assignment = Assignment::query()->create([
                'organization_id' => $job->organization_id,
                'tenant_id' => $job->tenant_id,
                'service_job_id' => $job->id,
                'executor_id' => $executor->id,
                'assignment_mode' => 'manual_override',
                'status' => AssignmentStatus::ACCEPTED->value,
                'accepted_at' => now(),
                'eta_at' => now()->addMinutes(25),
                'metadata' => [
                    'reason' => $reason,
                    'dispatched_by' => $dispatcherUserId,
                ],
            ]);

            $job->update([
                'executor_id' => $executor->id,
                'assignment_id' => $assignment->id,
            ]);

            $this->updateServiceJobStatusAction->execute(
                job: $job->fresh(),
                newStatus: ServiceJobStatus::ASSIGNED->value,
                reason: 'manual_dispatch',
                context: [
                    'assignment_id' => $assignment->id,
                    'executor_id' => $executor->id,
                ],
                actorType: 'dispatcher',
                actorId: $dispatcherUserId,
            );

            $this->writeJobTimelineAction->execute(
                job: $job->fresh(),
                eventType: 'assignment_created',
                actorType: 'dispatcher',
                actorId: $dispatcherUserId,
                assignmentId: $assignment->id,
                payload: [
                    'assignment_id' => $assignment->id,
                    'executor_id' => $executor->id,
                    'assignment_mode' => 'manual_override',
                    'status' => $assignment->status,
                    'reason' => $reason,
                ],
            );

            event(new ServiceJobBroadcasted(
                organizationId: $job->organization_id,
                jobId: $job->id,
                payload: [
                    'status' => ServiceJobStatus::ASSIGNED->value,
                    'reason' => 'manual_dispatch',
                    'executor_id' => $executor->id,
                    'assignment_id' => $assignment->id,
                    'updated_at' => now()->toIso8601String(),
                ],
            ));

            return $assignment->fresh();
        });
    }
}
