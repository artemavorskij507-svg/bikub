<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Dispatch\Enums\AssignmentStatus;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Operations\Actions\UpdateServiceJobStatusAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Enums\ServiceJobStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function accept(Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        DB::transaction(function () use ($assignment): void {
            $assignment->update([
                'status' => AssignmentStatus::ACCEPTED->value,
                'accepted_at' => now(),
            ]);
        });

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_accepted');

        return response()->json(['message' => 'Assignment accepted.']);
    }

    public function reject(
        Assignment $assignment,
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
    ): JsonResponse
    {
        $this->authorize('update', $assignment);

        DB::transaction(function () use ($assignment, $updateServiceJobStatusAction): void {
            $assignment->update([
                'status' => AssignmentStatus::REJECTED->value,
                'cancelled_at' => now(),
                'cancel_reason' => 'rejected_by_executor',
            ]);

            $updateServiceJobStatusAction->execute(
                job: $assignment->serviceJob,
                newStatus: ServiceJobStatus::PENDING_DISPATCH->value,
                reason: 'assignment_rejected',
                context: ['assignment_id' => $assignment->id],
                actorType: 'executor',
                actorId: optional($assignment->executor)->user_id,
            );
        });

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_rejected');

        return response()->json(['message' => 'Assignment rejected.']);
    }

    public function startTravel(
        Assignment $assignment,
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
    ): JsonResponse
    {
        $this->authorize('update', $assignment);

        DB::transaction(function () use ($assignment, $updateServiceJobStatusAction): void {
            $assignment->update([
                'status' => AssignmentStatus::ACTIVE->value,
                'started_at' => $assignment->started_at ?? now(),
            ]);

            $updateServiceJobStatusAction->execute(
                job: $assignment->serviceJob,
                newStatus: ServiceJobStatus::EN_ROUTE->value,
                reason: 'executor_started_travel',
                context: ['assignment_id' => $assignment->id],
                actorType: 'executor',
                actorId: optional($assignment->executor)->user_id,
            );
        });

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_travel_started');

        return response()->json(['message' => 'Travel started.']);
    }

    public function arrive(
        Assignment $assignment,
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
    ): JsonResponse
    {
        $this->authorize('update', $assignment);

        DB::transaction(function () use ($assignment, $updateServiceJobStatusAction): void {
            $assignment->update([
                'arrived_at' => now(),
            ]);

            $updateServiceJobStatusAction->execute(
                job: $assignment->serviceJob,
                newStatus: ServiceJobStatus::ARRIVED->value,
                reason: 'executor_arrived',
                context: ['assignment_id' => $assignment->id],
                actorType: 'executor',
                actorId: optional($assignment->executor)->user_id,
            );
        });

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_arrived');

        return response()->json(['message' => 'Executor arrived.']);
    }

    public function startWork(
        Assignment $assignment,
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
    ): JsonResponse
    {
        $this->authorize('update', $assignment);

        $updateServiceJobStatusAction->execute(
            job: $assignment->serviceJob,
            newStatus: ServiceJobStatus::IN_PROGRESS->value,
            reason: 'executor_started_work',
            context: ['assignment_id' => $assignment->id],
            actorType: 'executor',
            actorId: optional($assignment->executor)->user_id,
        );

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_work_started');

        return response()->json(['message' => 'Work started.']);
    }

    public function complete(
        Assignment $assignment,
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
    ): JsonResponse
    {
        $this->authorize('update', $assignment);

        DB::transaction(function () use ($assignment, $updateServiceJobStatusAction): void {
            $assignment->update([
                'status' => AssignmentStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            $updateServiceJobStatusAction->execute(
                job: $assignment->serviceJob,
                newStatus: ServiceJobStatus::COMPLETED->value,
                reason: 'assignment_completed',
                context: ['assignment_id' => $assignment->id],
                actorType: 'executor',
                actorId: optional($assignment->executor)->user_id,
            );
        });

        $this->writeAssignmentTimeline($assignment->fresh(), 'assignment_completed');

        return response()->json(['message' => 'Assignment completed.']);
    }

    private function writeAssignmentTimeline(Assignment $assignment, string $eventType): void
    {
        app(WriteJobTimelineAction::class)->execute(
            job: $assignment->serviceJob,
            eventType: $eventType,
            actorType: 'executor',
            actorId: optional($assignment->executor)->user_id,
            assignmentId: $assignment->id,
            payload: [
                'assignment_id' => $assignment->id,
                'executor_id' => $assignment->executor_id,
                'status' => $assignment->status,
            ],
        );
    }
}
