<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\Operations\Assignment;
use Carbon\CarbonInterface;

class WorkbenchLatencyQuery
{
    public function execute(string $organizationId): array
    {
        $organizationScope = (string) $organizationId;

        $pendingJobs = ServiceJob::query()
            ->where('organization_id', $organizationScope)
            ->where('status', 'pending_dispatch');
        $pendingJobsCount = (clone $pendingJobs)->count();
        $oldestPendingJob = (clone $pendingJobs)->oldest('created_at')->first(['id', 'created_at']);
        $pendingAge = $this->ageSeconds($oldestPendingJob?->created_at);

        $waitingAssignments = Assignment::query()
            ->where('organization_id', $organizationScope)
            ->whereIn('status', ['proposed', 'offered'])
            ->whereNotNull('acceptance_deadline_at');
        $waitingAssignmentsCount = (clone $waitingAssignments)->count();
        $oldestWaitingAssignment = (clone $waitingAssignments)->oldest('created_at')->first(['id', 'service_job_id', 'created_at', 'acceptance_deadline_at']);
        $waitingAcceptanceAge = $this->ageSeconds($oldestWaitingAssignment?->created_at);

        $ackOverdueExceptions = OperationException::query()
            ->where('organization_id', $organizationScope)
            ->where('status', 'open')
            ->whereNotNull('detected_at')
            ->where('detected_at', '<=', now()->subMinutes(15));
        $ackOverdueCount = (clone $ackOverdueExceptions)->count();
        $oldestAckOverdue = (clone $ackOverdueExceptions)->oldest('detected_at')->first(['id', 'service_job_id', 'detected_at']);
        $ackOverdueAge = $this->ageSeconds($oldestAckOverdue?->detected_at);

        $emergencyJobs = ServiceJob::query()
            ->where('organization_id', $organizationScope)
            ->where('service_domain', 'roadside')
            ->whereIn('status', ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->where(function ($query): void {
                $query->whereIn('priority', ['emergency', 'urgent'])
                    ->orWhereJsonContains('metadata->is_emergency', true);
            });
        $emergencyCount = (clone $emergencyJobs)->count();
        $oldestEmergency = (clone $emergencyJobs)->oldest('created_at')->first(['id', 'created_at']);
        $emergencyAge = $this->ageSeconds($oldestEmergency?->created_at);

        $cards = [
            [
                'key' => 'job_waiting_since',
                'label' => 'Job waiting since',
                'count' => $pendingJobsCount,
                'max_age_seconds' => $pendingAge,
                'max_age_human' => $this->formatAge($pendingAge),
                'severity' => $this->severityByAge($pendingAge, 900, 1800),
                'focus_job_id' => $oldestPendingJob?->id,
                'filter' => ['status' => 'pending_dispatch'],
            ],
            [
                'key' => 'assignment_acceptance_waiting',
                'label' => 'Acceptance waiting',
                'count' => $waitingAssignmentsCount,
                'max_age_seconds' => $waitingAcceptanceAge,
                'max_age_human' => $this->formatAge($waitingAcceptanceAge),
                'severity' => $this->severityByAge($waitingAcceptanceAge, 300, 900),
                'focus_job_id' => $oldestWaitingAssignment?->service_job_id,
                'filter' => ['status' => 'assigned'],
            ],
            [
                'key' => 'exception_ack_overdue',
                'label' => 'Exception ack overdue',
                'count' => $ackOverdueCount,
                'max_age_seconds' => $ackOverdueAge,
                'max_age_human' => $this->formatAge($ackOverdueAge),
                'severity' => $this->severityByAge($ackOverdueAge, 900, 1800),
                'focus_job_id' => $oldestAckOverdue?->service_job_id,
                'filter' => ['exceptions_only' => true],
            ],
            [
                'key' => 'emergency_response_age',
                'label' => 'Emergency response age',
                'count' => $emergencyCount,
                'max_age_seconds' => $emergencyAge,
                'max_age_human' => $this->formatAge($emergencyAge),
                'severity' => $this->severityByAge($emergencyAge, 180, 480),
                'focus_job_id' => $oldestEmergency?->id,
                'filter' => ['domain' => 'roadside', 'priority' => 'emergency'],
            ],
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'cards' => $cards,
            'max_age_seconds' => collect($cards)
                ->pluck('max_age_seconds')
                ->filter(fn ($value) => is_int($value) || is_float($value))
                ->max() ?: 0,
        ];
    }

    private function ageSeconds(?CarbonInterface $at): ?int
    {
        if (! $at) {
            return null;
        }

        return max(0, now()->diffInSeconds($at, false));
    }

    private function formatAge(?int $seconds): string
    {
        if ($seconds === null) {
            return 'n/a';
        }

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        if ($minutes < 60) {
            return $minutes . 'm ' . $remaining . 's';
        }

        $hours = intdiv($minutes, 60);
        $restMinutes = $minutes % 60;

        return $hours . 'h ' . $restMinutes . 'm';
    }

    private function severityByAge(?int $seconds, int $warningThreshold, int $dangerThreshold): string
    {
        if ($seconds === null || $seconds === 0) {
            return 'info';
        }

        if ($seconds >= $dangerThreshold) {
            return 'danger';
        }

        if ($seconds >= $warningThreshold) {
            return 'warning';
        }

        return 'info';
    }
}
