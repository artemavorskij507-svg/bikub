<?php

namespace App\Listeners;

use App\Domain\Exceptions\Events\OperationExceptionBroadcasted;
use App\Domain\Exceptions\Events\OperationExceptionOpened;

class BroadcastOperationExceptionOpened
{
    public function handle(OperationExceptionOpened $event): void
    {
        $exception = $event->exception;

        event(new OperationExceptionBroadcasted(
            organizationId: $exception->organization_id,
            exceptionId: $exception->id,
            payload: [
                'service_job_id' => $exception->service_job_id,
                'assignment_id' => $exception->assignment_id,
                'executor_id' => $exception->executor_id,
                'type' => $exception->type ?: $exception->exception_type,
                'severity' => $exception->severity,
                'status' => $exception->status,
                'detected_at' => optional($exception->detected_at)->toIso8601String(),
            ],
        ));
    }
}

