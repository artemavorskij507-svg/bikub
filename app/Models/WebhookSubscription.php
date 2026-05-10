<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'url',
        'secret',
        'events',
        'active',
        'timeout_seconds',
        'retry_attempts',
        'retry_delay_seconds',
        'last_triggered_at',
        'metadata',
    ];

    protected $casts = [
        'events' => 'array',
        'metadata' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'subscription_id');
    }

    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function trigger(string $event, array $payload): bool
    {
        if (! $this->active || ! $this->isSubscribedTo($event)) {
            return false;
        }

        $this->update(['last_triggered_at' => now()]);

        return $this->deliver($event, $payload);
    }

    private function deliver(string $event, array $payload, int $attempt = 1): bool
    {
        try {
            $signature = $this->generateSignature($payload);

            $response = Http::timeout($this->timeout_seconds)
                ->withHeaders([
                    'X-GLF-Signature' => $signature,
                    'X-GLF-Event' => $event,
                    'X-GLF-Attempt' => $attempt,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'GLF-BiKube-Webhook/1.0',
                ])
                ->post($this->url, $payload);

            $log = $this->logs()->create([
                'event' => $event,
                'payload' => $payload,
                'status_code' => $response->status(),
                'attempt' => $attempt,
                'response_body' => $response->body(),
                'response_time_ms' => $response->transferStats?->getHandlerStat('total_time') * 1000,
                'delivered_at' => $response->successful() ? now() : null,
                'error_message' => $response->failed() ? $response->body() : null,
            ]);

            if ($response->successful()) {
                return true;
            }

            // Retry logic
            if ($attempt < $this->retry_attempts) {
                sleep($this->retry_delay_seconds);

                return $this->deliver($event, $payload, $attempt + 1);
            }

            return false;

        } catch (\Exception $e) {
            $this->logs()->create([
                'event' => $event,
                'payload' => $payload,
                'attempt' => $attempt,
                'error_message' => $e->getMessage(),
                'delivered_at' => null,
            ]);

            Log::error('Webhook delivery failed', [
                'subscription_id' => $this->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            // Retry logic
            if ($attempt < $this->retry_attempts) {
                sleep($this->retry_delay_seconds);

                return $this->deliver($event, $payload, $attempt + 1);
            }

            return false;
        }
    }

    private function generateSignature(array $payload): string
    {
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return 'sha256='.hash_hmac('sha256', $payloadString, $this->secret);
    }

    public static function verifySignature(string $signature, array $payload, string $secret): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function getDeliveryStats(): array
    {
        $logs = $this->logs()->get();

        $total = $logs->count();
        $successful = $logs->where('status_code', '>=', 200)->where('status_code', '<', 300)->count();
        $failed = $total - $successful;

        $avgResponseTime = $logs->whereNotNull('response_time_ms')->avg('response_time_ms');

        return [
            'total_deliveries' => $total,
            'successful_deliveries' => $successful,
            'failed_deliveries' => $failed,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
            'average_response_time_ms' => round($avgResponseTime ?? 0, 2),
            'last_delivery' => $this->last_triggered_at,
        ];
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function updateSecret(string $newSecret): void
    {
        $this->update(['secret' => $newSecret]);
    }

    public function addEvent(string $event): void
    {
        $events = $this->events ?? [];
        if (! in_array($event, $events)) {
            $events[] = $event;
            $this->update(['events' => $events]);
        }
    }

    public function removeEvent(string $event): void
    {
        $events = $this->events ?? [];
        $events = array_filter($events, fn ($e) => $e !== $event);
        $this->update(['events' => array_values($events)]);
    }
}
