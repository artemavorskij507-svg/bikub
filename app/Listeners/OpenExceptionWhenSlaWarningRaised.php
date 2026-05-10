<?php

namespace App\Listeners;

use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OpenExceptionWhenSlaWarningRaised
{
    public function handle(object $event): void
    {
        $job = $event->serviceJob ?? $event->job ?? null;
        if (! $job instanceof ServiceJob) {
            return;
        }

        $phase = (string) ($event->phase ?? 'completion');
        $type = 'sla_warning_'.$phase;

        $this->openIfMissing($job, $type, 'medium', 'SLA warning raised');
    }

    private function openIfMissing(ServiceJob $job, string $type, string $severity, string $summary): void
    {
        if (! Schema::hasTable('operation_exceptions')) {
            return;
        }

        try {
            $exists = OperationException::query()
                ->where('service_job_id', $job->id)
                ->where('type', $type)
                ->whereIn('status', ['open', 'acknowledged'])
                ->exists();

            if ($exists) {
                return;
            }

            OperationException::query()->create([
                'organization_id' => $job->organization_id,
                'tenant_id' => $job->tenant_id,
                'service_job_id' => $job->id,
                'executor_id' => $job->executor_id,
                'type' => $type,
                'severity' => $severity,
                'status' => 'open',
                'detected_by' => 'system',
                'detected_at' => now(),
                'summary' => $summary,
                'payload' => ['job_status' => $job->status],
            ]);
        } catch (\Throwable $e) {
            Log::warning('OpenExceptionWhenSlaWarningRaised failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
