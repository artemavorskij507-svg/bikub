<?php

namespace App\Domain\Ops\Actions;

use Illuminate\Support\Facades\Log;

class RecordWorkbenchActionAudit
{
    public function execute(
        int $actorUserId,
        string $action,
        bool $success,
        ?string $targetType = null,
        ?int $targetId = null,
        array $payload = [],
        ?int $jobId = null,
        ?int $executorId = null,
        ?int $exceptionId = null,
        ?string $message = null,
    ): void {
        Log::info('ops.workbench.action', [
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'success' => $success,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'job_id' => $jobId,
            'executor_id' => $executorId,
            'exception_id' => $exceptionId,
            'message' => $message,
            'payload' => $payload,
            'happened_at' => now()->toIso8601String(),
        ]);
    }
}
