<?php

namespace App\Jobs\Partners;

use App\Models\Partner;
use App\Models\PartnerWebhookLog;
use App\Services\Partners\PartnerWebhookDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchPartnerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 7;

    public function __construct(public int $logId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600, 10800, 21600, 86400];
    }

    public function handle(): void
    {
        $log = PartnerWebhookLog::find($this->logId);
        if (! $log) {
            return;
        }
        $partner = Partner::find($log->partner_id);
        if (! $partner || ! $partner->webhook_url || ! $partner->active) {
            $log->update(['status' => 'abandoned']);

            return;
        }

        $raw = json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = PartnerWebhookDispatcher::sign($partner->webhook_secret ?? '', $raw);

        try {
            $resp = Http::timeout(8)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-GLF-Signature' => $signature,
                    'Idempotency-Key' => $log->idempotency_key,
                ])->post($partner->webhook_url, $log->payload);

            $status = $resp->status();
            $ok = $status >= 200 && $status < 300;
            $log->update([
                'response_status' => $status,
                'response_body' => $resp->body(),
                'status' => $ok ? 'ok' : 'failed',
                'signature_sent' => $signature,
                'delivered_at' => $ok ? now() : null,
                'next_retry_at' => $ok ? null : now()->addSeconds($this->backoff()[min($this->attempts() - 1, count($this->backoff()) - 1)]),
            ]);

            if (! $ok) {
                $this->release($this->backoff()[min($this->attempts() - 1, count($this->backoff()) - 1)]);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'response_body' => $e->getMessage(), 'next_retry_at' => now()->addMinutes(5)]);
            $this->release(300);
        }
    }
}
