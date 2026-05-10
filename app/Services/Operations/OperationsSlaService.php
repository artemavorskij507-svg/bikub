<?php

namespace App\Services\Operations;

use App\Events\Operations\ExceptionOpened;
use App\Events\Operations\SlaBreached;
use App\Events\Operations\SlaWarningRaised;
use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;
use App\Models\Operations\SlaTimer;

class OperationsSlaService
{
    public function ensureTimers(ServiceJob $job): SlaTimer
    {
        $minutes = max(5, (int) ($job->promised_sla_minutes ?? 60));
        $dispatchMinutes = max(2, (int) floor($minutes * 0.1));
        $arrivalMinutes = max(5, (int) floor($minutes * 0.5));

        return SlaTimer::updateOrCreate(
            ['service_job_id' => $job->id],
            [
                'organization_id' => $job->organization_id,
                'dispatch_deadline_at' => $job->created_at?->copy()->addMinutes($dispatchMinutes) ?? now()->addMinutes($dispatchMinutes),
                'arrival_deadline_at' => $job->created_at?->copy()->addMinutes($arrivalMinutes) ?? now()->addMinutes($arrivalMinutes),
                'completion_deadline_at' => $job->created_at?->copy()->addMinutes($minutes) ?? now()->addMinutes($minutes),
            ]
        );
    }

    public function evaluate(ServiceJob $job): array
    {
        $timer = $this->ensureTimers($job);
        $now = now();

        $dispatchState = $this->computeState($timer->dispatch_deadline_at, $job->status, ['assigned', 'accepted', 'arrived', 'started', 'completed']);
        $arrivalState = $this->computeState($timer->arrival_deadline_at, $job->status, ['arrived', 'started', 'completed']);
        $completionState = $this->computeState($timer->completion_deadline_at, $job->status, ['completed']);

        $timer->update([
            'dispatch_state' => $dispatchState,
            'arrival_state' => $arrivalState,
            'completion_state' => $completionState,
            'last_evaluated_at' => $now,
        ]);

        $states = [
            'dispatch' => $dispatchState,
            'arrival' => $arrivalState,
            'completion' => $completionState,
        ];

        foreach ($states as $phase => $state) {
            if ($state === 'warning') {
                event(new SlaWarningRaised($job, $phase, $timer));
            }
            if ($state === 'breached') {
                event(new SlaBreached($job, $phase, $timer));
                $this->openExceptionIfMissing($job, 'sla_breach', "SLA breached on {$phase} stage");
            }
        }

        return [
            'timer' => $timer->fresh(),
            'states' => $states,
        ];
    }

    public function openExceptionIfMissing(ServiceJob $job, string $type, string $summary, ?int $assignmentId = null): OperationException
    {
        $existing = OperationException::where('service_job_id', $job->id)
            ->where('exception_type', $type)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $existing;
        }

        $exception = OperationException::create([
            'organization_id' => $job->organization_id,
            'service_job_id' => $job->id,
            'assignment_id' => $assignmentId,
            'exception_type' => $type,
            'severity' => $type === 'sla_breach' ? 'high' : 'medium',
            'status' => 'open',
            'detected_at' => now(),
            'summary' => $summary,
            'remediation' => [
                'actions' => [],
                'auto_escalated' => false,
            ],
        ]);

        event(new ExceptionOpened($exception));

        return $exception;
    }

    private function computeState($deadline, string $jobStatus, array $doneStatuses): string
    {
        if (! $deadline) {
            return 'ok';
        }

        if (in_array($jobStatus, $doneStatuses, true)) {
            return 'ok';
        }

        $minutesToDeadline = now()->diffInMinutes($deadline, false);
        if ($minutesToDeadline < 0) {
            return 'breached';
        }
        if ($minutesToDeadline <= 15) {
            return 'warning';
        }

        return 'ok';
    }
}

