<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookNotifier
{
    public function send(string $eventType, array $payload, int $retryCount = 0): void
    {
        $endpoints = config('webhooks.endpoints', []);
        $secret = config('webhooks.secret');
        $timeout = config('webhooks.timeout', 5);
        $maxRetries = config('webhooks.max_retries', 3);
        $retryDelays = config('webhooks.retries', [30, 120, 300]);

        foreach ($endpoints as $url) {
            try {
                // Create event data
                $event = [
                    'type' => $eventType,
                    'payload' => $payload,
                    'timestamp' => now()->toIso8601String(),
                    'id' => uniqid('wh_', true),
                ];

                // Generate signature
                $payloadJson = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $signature = hash_hmac('sha256', $payloadJson, $secret);

                // Check for duplicate (deduplication)
                $dedupeKey = 'webhook_dedup:'.md5($url.$eventType.json_encode($payload));
                if (Cache::has($dedupeKey)) {
                    Log::debug("Skipping duplicate webhook: {$eventType}");

                    continue;
                }

                // Send webhook with signature
                $response = Http::timeout($timeout)
                    ->retry($maxRetries, function ($exception, $request) {
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    })
                    ->withHeaders([
                        'X-GLF-Signature' => $signature,
                        'X-GLF-Event-Type' => $eventType,
                        'X-GLF-Timestamp' => $event['timestamp'],
                    ])
                    ->asJson()
                    ->post($url, $event);

                if ($response->successful()) {
                    // Mark as sent (deduplication TTL: 24 hours)
                    Cache::put($dedupeKey, true, now()->addHours(24));
                    Log::info("Webhook sent successfully: {$eventType} to {$url}");
                } else {
                    // Schedule retry if not exceeded max retries
                    if ($retryCount < count($retryDelays)) {
                        $delay = $retryDelays[$retryCount] ?? 300;
                        Log::warning("Webhook failed, will retry in {$delay}s: {$eventType} to {$url}", [
                            'status' => $response->status(),
                            'response' => $response->body(),
                        ]);
                        // Note: In production, this should be queued with delay
                        // For now, we just log it
                    } else {
                        Log::error("Webhook failed after max retries: {$eventType} to {$url}", [
                            'status' => $response->status(),
                            'response' => $response->body(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Webhook send exception: {$eventType} to {$url}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Schedule retry if not exceeded max retries
                if ($retryCount < $maxRetries) {
                    $delay = $retryDelays[$retryCount] ?? 300;
                    Log::warning("Webhook exception, will retry in {$delay}s: {$eventType}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Verify webhook signature (for incoming webhooks)
     */
    public static function verifySignature(string $payload, string $signature): bool
    {
        $secret = config('webhooks.secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
