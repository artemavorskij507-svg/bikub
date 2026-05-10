<?php

namespace App\Listeners;

use App\Models\Operations\ServiceJob;
use App\Models\Operations\SlaTimer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateSlaTimersWhenServiceJobCreated
{
    public function handle(object $event): void
    {
        $job = $event->job ?? null;
        if (! $job instanceof ServiceJob) {
            return;
        }

        if (! Schema::hasTable('sla_timers')) {
            return;
        }

        try {
            if (SlaTimer::query()->where('service_job_id', $job->id)->exists()) {
                return;
            }

            $columns = Schema::getColumnListing('sla_timers');
            $columnSet = array_flip($columns);

            $start = $job->time_window_start
                ? Carbon::parse($job->time_window_start)
                : now();
            $duration = (int) ($job->service_duration_minutes ?? 30);
            if ($duration <= 0) {
                $duration = 30;
            }
            $end = $job->time_window_end
                ? Carbon::parse($job->time_window_end)
                : $start->copy()->addMinutes($duration);

            $row = [
                'organization_id' => $job->organization_id,
                'tenant_id' => $job->tenant_id,
                'service_job_id' => $job->id,
                'assignment_id' => $job->assignment_id,
                'sla_policy_id' => $job->sla_policy_id,
                'metric_name' => 'completion',
                'target_at' => $end,
                'warning_at' => $end->copy()->subMinutes(15),
                'breach_at' => $end,
                'status' => 'active',
                'dispatch_deadline_at' => $start->copy()->addMinutes(10),
                'arrival_deadline_at' => $start->copy()->addMinutes(20),
                'completion_deadline_at' => $end,
                'dispatch_state' => 'on_track',
                'arrival_state' => 'on_track',
                'completion_state' => 'on_track',
                'last_evaluated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            SlaTimer::query()->create(array_intersect_key($row, $columnSet));
        } catch (\Throwable $e) {
            Log::warning('CreateSlaTimersWhenServiceJobCreated failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
