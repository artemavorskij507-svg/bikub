<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Events\ServiceJobStatusChanged;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Support\Facades\DB;

class UpdateServiceJobStatusAction
{
    public function execute(
        ServiceJob $job,
        string $newStatus,
        ?string $reason = null,
        array $context = [],
        string $actorType = 'system',
        ?int $actorId = null,
    ): ServiceJob {
        $oldStatus = (string) $job->status;
        if ($oldStatus === $newStatus) {
            return $job;
        }

        DB::transaction(function () use ($job, $newStatus, $reason, $context, $actorType, $actorId, $oldStatus): void {
            $job->update(['status' => $newStatus]);

            event(new ServiceJobStatusChanged(
                job: $job->fresh(),
                from: $oldStatus,
                to: $newStatus,
                reason: $reason,
                context: array_merge($context, [
                    'actor_type' => $actorType,
                    'actor_id' => $actorId,
                ]),
            ));
        });

        return $job->fresh();
    }
}

