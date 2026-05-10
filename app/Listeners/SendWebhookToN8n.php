<?php

namespace App\Listeners;

use App\Events\AdExpiredEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookToN8n
{
    /**
     * Отправка веб‑хука в n8n при истечении срока объявления.
     */
    public function handle(AdExpiredEvent $event): void
    {
        $url = config('services.n8n.classifieds_expired_webhook')
            ?: config('services.n8n.webhook_url');

        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'event' => 'ad_expired',
                'ad_id' => $event->ad->id,
                'title' => $event->ad->title,
                'user_email' => $event->ad->user->email ?? null,
                'expired_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('N8N Webhook failed: '.$e->getMessage());
        }
    }
}
