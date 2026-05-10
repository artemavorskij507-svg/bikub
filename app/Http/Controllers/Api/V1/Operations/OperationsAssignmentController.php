<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Events\Operations\AssignmentAccepted;
use App\Events\Operations\JobCompleted;
use App\Events\Operations\JobStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Operations\Assignment;
use App\Models\Operations\JobStateTransition;
use App\Services\Operations\JobTimelineService;
use App\Services\Operations\OperationsSlaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsAssignmentController extends Controller
{
    public function __construct(
        private readonly OperationsSlaService $slaService,
        private readonly JobTimelineService $timelineService
    ) {}

    public function accept(Request $request, int $id)
    {
        return $this->transition($id, 'accepted', 'assignment.accepted', 'accepted_at');
    }

    public function start(Request $request, int $id)
    {
        return $this->transition($id, 'started', 'assignment.started', 'started_at');
    }

    public function arrive(Request $request, int $id)
    {
        return $this->transition($id, 'arrived', 'assignment.arrived', 'arrived_at');
    }

    public function complete(Request $request, int $id)
    {
        $response = $this->transition($id, 'completed', 'assignment.completed', 'completed_at');
        $assignment = Assignment::find($id);
        if ($assignment && $assignment->serviceJob) {
            $assignment->serviceJob->update([
                'status' => 'completed',
                'actual_completed_at' => now(),
            ]);
            event(new JobCompleted($assignment->serviceJob->fresh()));
            $this->slaService->evaluate($assignment->serviceJob->fresh());
        }

        return $response;
    }

    private function transition(int $assignmentId, string $toStatus, string $eventType, string $timestampField)
    {
        $assignment = Assignment::with('serviceJob')->find($assignmentId);
        if (! $assignment) {
            throw ValidationException::withMessages(['assignment' => 'Assignment not found']);
        }

        $oldStatus = $assignment->status;
        $assignment->update([
            'status' => $toStatus,
            $timestampField => now(),
        ]);

        if ($assignment->serviceJob) {
            $jobOldStatus = $assignment->serviceJob->status;
            $mappedJobStatus = $this->mapAssignmentStatusToJob($toStatus);
            $assignment->serviceJob->update([
                'status' => $mappedJobStatus,
                'actual_started_at' => $toStatus === 'started' ? now() : $assignment->serviceJob->actual_started_at,
            ]);
            event(new JobStatusChanged($assignment->serviceJob->fresh(), $jobOldStatus, $assignment->serviceJob->status));
            if ($toStatus === 'accepted') {
                event(new AssignmentAccepted($assignment->fresh('serviceJob')));
            }

            JobStateTransition::create([
                'service_job_id' => $assignment->service_job_id,
                'assignment_id' => $assignment->id,
                'from_status' => $jobOldStatus,
                'to_status' => $assignment->serviceJob->status,
                'event_type' => $eventType,
                'actor_id' => auth()->id(),
                'actor_type' => auth()->check() ? get_class(auth()->user()) : null,
                'payload' => [
                    'assignment_status_from' => $oldStatus,
                    'assignment_status_to' => $toStatus,
                ],
                'transitioned_at' => now(),
            ]);

            $this->timelineService->log(
                $assignment->serviceJob,
                $eventType,
                [
                    'assignment_status_from' => $oldStatus,
                    'assignment_status_to' => $toStatus,
                ],
                $assignment,
                auth()->check() ? 'dispatcher' : 'system',
                auth()->id()
            );

            $this->slaService->evaluate($assignment->serviceJob->fresh());
        }

        return response()->json([
            'success' => true,
            'data' => $assignment->fresh('serviceJob'),
        ]);
    }

    private function mapAssignmentStatusToJob(string $assignmentStatus): string
    {
        return match ($assignmentStatus) {
            'accepted' => 'accepted',
            'arrived' => 'arrived',
            'started' => 'started',
            'completed' => 'completed',
            default => 'assigned',
        };
    }
}
