<?php

namespace App\Domain\Exceptions\Actions;

use App\Domain\Exceptions\Events\OperationExceptionBroadcasted;
use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Models\ServiceJob;

class ResolveOperationExceptionAction
{
    public function __construct(
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
    ) {}

    public function execute(
        OperationException $exception,
        int $userId,
        string $resolutionCode,
        ?string $resolutionNotes = null,
        ?string $rootCause = null,
    ): OperationException {
        $exception->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'owner_user_id' => $userId,
            'resolution_code' => $resolutionCode,
            'resolution_notes' => $resolutionNotes,
            'root_cause' => $rootCause,
        ]);

        $job = ServiceJob::query()->find($exception->service_job_id);
        if ($job) {
            $this->writeJobTimelineAction->execute(
                job: $job,
                eventType: 'operation_exception_resolved',
                actorType: 'dispatcher',
                actorId: $userId,
                assignmentId: $exception->assignment_id,
                payload: [
                    'exception_id' => $exception->id,
                    'type' => $exception->canonical_type,
                    'resolution_code' => $resolutionCode,
                    'root_cause' => $rootCause,
                ],
            );
        }

        event(new OperationExceptionBroadcasted(
            organizationId: $exception->organization_id,
            exceptionId: $exception->id,
            payload: [
                'status' => 'resolved',
                'resolution_code' => $resolutionCode,
                'service_job_id' => $exception->service_job_id,
                'assignment_id' => $exception->assignment_id,
                'executor_id' => $exception->executor_id,
                'updated_at' => now()->toIso8601String(),
            ],
        ));

        return $exception->fresh();
    }
}

