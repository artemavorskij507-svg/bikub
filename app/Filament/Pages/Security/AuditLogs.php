<?php

namespace App\Filament\Pages\Security;

use App\Models\AuditLog;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class AuditLogs extends Page
{
    protected static string $view = 'filament.security.audit-logs';

    protected static ?string $title = 'Audit Logs';

    public function mount(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('audit_logs')) {
            return;
        }

        if (AuditLog::query()->exists()) {
            return;
        }

        AuditLog::query()->create([
            'actor_user_id' => auth()->id(),
            'action' => 'local_demo_seed',
            'model_type' => static::class,
            'model_id' => null,
            'before' => null,
            'after' => ['status' => 'initialized'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}
