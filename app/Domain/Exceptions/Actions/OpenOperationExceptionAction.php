<?php

namespace App\Domain\Exceptions\Actions;

use App\Domain\Exceptions\Events\OperationExceptionOpened;
use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Models\ServiceJob;

class OpenOperationExceptionAction
{
    public function __construct(
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
    ) {}

    public function execute(
        ServiceJob $job,
        string $type,
        string $severity = 'medium',
        ?int $assignmentId = null,
        ?int $executorId = null,
        string $detectedBy = 'system',
        array $payload = [],
    ): OperationException {
        $activeStatuses = ['open', 'acknowledged', 'investigating', 'mitigated'];

        $existing = OperationException::query()
            ->where('service_job_id', $job->id)
            ->where(function ($q) use ($type): void {
                $q->where('type', $type)->orWhere('exception_type', $type);
            })
            ->whereIn('status', $activeStatuses)
            ->first();

        if ($existing) {
            return $existing;
        }

        $exception = OperationException::query()->create([
            'organization_id' => $job->organization_id,
            'tenant_id' => $job->tenant_id,
            'service_job_id' => $job->id,
            'assignment_id' => $assignmentId,
            'executor_id' => $executorId,
            'type' => $type,
            'exception_type' => $type,
            'severity' => $severity,
            'status' => 'open',
            'detected_by' => $detectedBy,
            'detected_at' => now(),
            'payload' => $payload,
            'summary' => $payload['summary'] ?? null,
        ]);

        $this->writeJobTimelineAction->execute(
            job: $job,
            eventType: 'operation_exception_opened',
            actorType: 'system',
            assignmentId: $assignmentId,
            payload: [
                'exception_id' => $exception->id,
                'type' => $type,
                'severity' => $severity,
                'payload' => $payload,
            ],
        );

        event(new OperationExceptionOpened($exception->fresh()));

        return $exception->fresh();
    }
}

