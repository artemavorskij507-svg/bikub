<?php

namespace App\Listeners;

use App\Events\HandymanOrderRequested;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class EmitOrderWebhookForN8n
{
    public function handle(HandymanOrderRequested $event): void
    {
        if (! Config::get('services.n8n.enabled')) {
            return;
        }

        $order = $event->order;

        Http::asJson()
            ->timeout(5)
            ->post(Config::get('services.n8n.webhook_url'), [
                'event' => 'handyman.order_requested',
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'service_type' => $order->service_type,
                'status' => $order->status,
                'created_at' => $order->created_at?->toIso8601String(),
            ]);
    }
}
