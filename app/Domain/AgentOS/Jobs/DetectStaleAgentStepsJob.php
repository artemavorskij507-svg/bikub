<?php

namespace App\Domain\AgentOS\Jobs;

use App\Domain\AgentOS\Actions\UpdateAgentStepStatusAction;
use App\Domain\AgentOS\Enums\AgentStepStatus;
use App\Domain\AgentOS\Events\AgentStepMarkedStale;
use App\Domain\AgentOS\Models\AgentStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class DetectStaleAgentStepsJob implements ShouldQueue, NotTenantAware
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $graceMinutes = 2,
    ) {
    }

    public function handle(UpdateAgentStepStatusAction $updateAction): void
    {
        $now = now();
        $threshold = $now->copy()->subMinutes(max(1, $this->graceMinutes));

        AgentStep::query()
            ->where('status', AgentStepStatus::EXECUTING->value)
            ->where(function ($q) use ($threshold, $now): void {
                $q->where(function ($inner) use ($threshold): void {
                    $inner->whereNotNull('heartbeat_at')
                        ->where('heartbeat_at', '<=', $threshold);
                })->orWhere(function ($inner) use ($now): void {
                    $inner->whereNotNull('timeout_at')
                        ->where('timeout_at', '<=', $now);
                });
            })
            ->chunkById(100, function ($steps) use ($updateAction): void {
                foreach ($steps as $step) {
                    $hasRetryBudget = $step->retry_count < $step->max_retries;
                    $toStatus = $hasRetryBudget
                        ? AgentStepStatus::BLOCKED->value
                        : AgentStepStatus::FAILED->value;

                    if ($hasRetryBudget) {
                        $step->increment('retry_count');
                        $step->refresh();
                    }

                    $reason = $step->timeout_at && $step->timeout_at <= now()
                        ? 'step_timeout_reached'
                        : 'step_heartbeat_stale';

                    $updated = $updateAction->execute($step, $toStatus, [
                        'system_note' => sprintf('Step stale detected (%s).', $reason),
                    ]);

                    event(new AgentStepMarkedStale($updated, $reason));
                }
            });
    }
}
