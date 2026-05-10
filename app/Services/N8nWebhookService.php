<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nWebhookService
{
    protected ?string $webhookUrl;

    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = Config::get('services.n8n.enabled', false);
        $this->webhookUrl = Config::get('services.n8n.webhook_url');
    }

    public function send(string $event, array $payload): bool
    {
        if (! $this->enabled || ! $this->webhookUrl) {
            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(5)
                ->post($this->webhookUrl, [
                    'event' => $event,
                    'payload' => $payload,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                Log::info("N8n webhook sent: {$event}", ['payload' => $payload]);

                return true;
            }

            Log::warning("N8n webhook failed: {$event}", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error("N8n webhook error: {$event}", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    public function sendClaimCreated(array $claimData): bool
    {
        return $this->send('claim.created', $claimData);
    }

    public function sendClaimMessageCreated(array $messageData): bool
    {
        return $this->send('claim.message_created', $messageData);
    }

    public function sendClaimSlaBreached(array $breachData): bool
    {
        return $this->send('claim.sla_breached', $breachData);
    }

    public function sendHandymanOrder(array $orderData): bool
    {
        return $this->send('handyman.order_created', $orderData);
    }

    public function sendRepairProject(array $projectData): bool
    {
        return $this->send('repair.project_created', $projectData);
    }

    public function sendRepairUpdate(array $updateData): bool
    {
        return $this->send('repair.update_created', $updateData);
    }
}
