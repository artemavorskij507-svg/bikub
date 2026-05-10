<?php

namespace App\Services\Partners;

use App\Models\Partner;
use App\Models\PartnerWebhookLog;
use Illuminate\Support\Str;

class PartnerWebhookDispatcher
{
    public function dispatch(Partner $partner, string $eventType, array $payload): PartnerWebhookLog
    {
        $log = PartnerWebhookLog::create([
            'partner_id' => $partner->id,
            'event_type' => $eventType,
            'idempotency_key' => (string) Str::uuid(),
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Immediate attempt; further retries handled by job
        dispatch(new \App\Jobs\Partners\DispatchPartnerWebhookJob($log->id))->onQueue('webhooks');

        return $log;
    }

    public static function sign(string $secret, string $rawBody): string
    {
        return 'sha256='.hash_hmac('sha256', $rawBody, $secret);
    }
}
