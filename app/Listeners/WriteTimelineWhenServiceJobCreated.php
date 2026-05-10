<?php

namespace App\Listeners;

use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\Log;

class WriteTimelineWhenServiceJobCreated
{
    public function __construct(
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
    ) {}

    public function handle(object $event): void
    {
        $job = $event->job ?? null;
        if (! $job instanceof ServiceJob) {
            return;
        }

        try {
            $this->writeJobTimelineAction->execute(
                job: $job,
                eventType: 'service_job_created',
                actorType: 'system',
                actorId: null,
                assignmentId: $job->assignment_id,
                payload: [
                    'status' => $job->status,
                    'service_domain' => $job->service_domain,
                    'job_kind' => $job->job_kind,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('WriteTimelineWhenServiceJobCreated failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
