<?php

namespace App\Listeners;

use App\Events\Operations\SlaWarningRaised as OpsSlaWarningRaised;
use App\Models\Operations\ServiceJob;
use App\Models\Operations\SlaTimer;
use Illuminate\Support\Facades\Log;

class BroadcastSlaWarningBridge
{
    public function handle(object $event): void
    {
        $job = $this->resolveJob($event);
        $timer = $this->resolveTimer($event);
        $phase = (string) ($event->phase ?? 'completion');

        if (! $job instanceof ServiceJob || ! $timer instanceof SlaTimer) {
            return;
        }

        try {
            event(new OpsSlaWarningRaised($job, $phase, $timer));
        } catch (\Throwable $e) {
            Log::warning('BroadcastSlaWarningBridge failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveJob(object $event): ?ServiceJob
    {
        $candidate = $event->serviceJob ?? $event->job ?? null;

        return $candidate instanceof ServiceJob ? $candidate : null;
    }

    private function resolveTimer(object $event): ?SlaTimer
    {
        $candidate = $event->timer ?? null;

        return $candidate instanceof SlaTimer ? $candidate : null;
    }
}
