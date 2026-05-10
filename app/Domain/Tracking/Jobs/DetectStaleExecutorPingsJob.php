<?php

namespace App\Domain\Tracking\Jobs;

use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class DetectStaleExecutorPingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $staleAfterMinutes = 5,
    ) {}

    public function handle(OpenOperationExceptionAction $openOperationExceptionAction): void
    {
        $executors = Executor::query()
            ->whereIn('status', ['available', 'busy'])
            ->get();

        foreach ($executors as $executor) {
            $lastSeenAt = Redis::get("executor:{$executor->id}:last_seen_at");
            if (! $lastSeenAt) {
                continue;
            }

            $lastSeen = Carbon::parse($lastSeenAt);
            if ($lastSeen->gt(now()->subMinutes($this->staleAfterMinutes))) {
                continue;
            }

            $activeJob = ServiceJob::query()
                ->where('executor_id', $executor->id)
                ->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress'])
                ->latest('id')
                ->first();

            if (! $activeJob) {
                continue;
            }

            $openOperationExceptionAction->execute(
                job: $activeJob,
                type: 'stale_location_ping',
                severity: 'high',
                assignmentId: $activeJob->assignment_id,
                executorId: $executor->id,
                detectedBy: 'system',
                payload: [
                    'last_seen_at' => $lastSeen->toIso8601String(),
                    'threshold_minutes' => $this->staleAfterMinutes,
                ],
            );
        }
    }
}

