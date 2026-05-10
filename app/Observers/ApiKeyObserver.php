<?php

namespace App\Observers;

use App\Models\ApiKey;
use App\Services\AuditLogger;

class ApiKeyObserver
{
    protected function audit(string $action, ?ApiKey $model, $before = null, $after = null)
    {
        try {
            app(AuditLogger::class)->log(
                $action,
                ApiKey::class,
                $model?->id,
                $before,
                $after,
                request()
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Audit logging failed for ApiKeyObserver', ['error' => $e->getMessage()]);
        }
    }

    public function created(ApiKey $model)
    {
        // Log creation with key_hash and owner info (but NOT plaintext — that's handled in Resource)
        $this->audit('api_key_created', $model, null, [
            'owner_type' => $model->owner_type,
            'owner_id' => $model->owner_id,
            'name' => $model->name,
            'scopes' => $model->scopes,
            'expires_at' => $model->expires_at?->toDateTimeString(),
        ]);
    }

    public function updated(ApiKey $model)
    {
        $changes = $model->getChanges();

        // Remove timestamp-only changes
        $changes = array_diff_key($changes, array_flip(['updated_at']));

        if (empty($changes)) {
            return;
        }

        // Only meaningful updates (e.g., revoked_at, expires_at). Scopes should not change outside of rotation.
        $before = array_intersect_key($model->getOriginal(), $changes);
        $after = $changes;
        $this->audit('api_key_updated', $model, $before, $after);
    }

    public function deleted(ApiKey $model)
    {
        // Hard delete is discouraged; soft delete or revoke preferred. But if deleted, log it.
        $this->audit('api_key_deleted', $model, [
            'owner_type' => $model->owner_type,
            'owner_id' => $model->owner_id,
            'name' => $model->name,
            'scopes' => $model->scopes,
        ], null);
    }
}
