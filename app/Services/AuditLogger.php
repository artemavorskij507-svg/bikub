<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    /**
     * Log a generic audit entry. Use observers or explicit calls.
     *
     * @gdpr-critical: stores IP and user-agent which are personal data
     */
    public function log(string $action, ?string $modelType = null, ?int $modelId = null, $before = null, $after = null, ?Request $request = null): ?AuditLog
    {
        // Defensive: if audit_logs table or model is missing, skip auditing to avoid breaking flows
        if (! Schema::hasTable('audit_logs') || ! class_exists(AuditLog::class)) {
            Log::warning('AuditLog unavailable — skipping audit entry', ['action' => $action, 'model' => $modelType]);

            return null;
        }

        $user = Auth::user();
        $ip = $request?->ip() ?? request()->ip();
        $ua = $request?->userAgent() ?? request()->userAgent();
        $rid = $request?->attributes->get('request_id') ?? request()->attributes->get('request_id');

        try {
            return AuditLog::create([
                'actor_user_id' => $user?->id,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'before' => $before ? json_encode($before) : null,
                'after' => $after ? json_encode($after) : null,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'request_id' => $rid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create audit log entry', ['error' => $e->getMessage(), 'action' => $action, 'model' => $modelType]);

            return null;
        }
    }
}
