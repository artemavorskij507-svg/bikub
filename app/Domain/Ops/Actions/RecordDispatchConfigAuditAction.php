<?php

namespace App\Domain\Ops\Actions;

use App\Models\AuditLog;

class RecordDispatchConfigAuditAction
{
    public function execute(
        int|string|null $actorUserId,
        string $action,
        string $modelType,
        int|string|null $modelId,
        array $before = [],
        array $after = [],
        array $context = [],
    ): void {
        $afterWithContext = $after;
        if ($context !== []) {
            $afterWithContext['_config_context'] = $context;
        }

        AuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => (string) ($modelId ?? ''),
            'before' => $before,
            'after' => $afterWithContext,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'request_id' => (string) (request()?->headers->get('X-Request-Id') ?? ''),
        ]);

        logger()->info('dispatch_config_audit', [
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'context' => $context,
        ]);
    }
}
