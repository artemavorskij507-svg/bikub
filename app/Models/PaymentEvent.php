<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'idempotency_key',
        'provider',
        'type',
        'payload',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Check if event is already processed.
     */
    public static function isProcessed(string $idempotencyKey): bool
    {
        return self::where('idempotency_key', $idempotencyKey)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update(['status' => 'processed']);
    }

    /**
     * Mark event as failed.
     */
    public function markAsFailed(?string $error = null): void
    {
        $this->update([
            'status' => 'failed',
            'payload' => array_merge($this->payload ?? [], [
                'error' => $error,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Get event by idempotency key.
     */
    public static function findByKey(string $idempotencyKey): ?self
    {
        return self::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Create new payment event.
     */
    public static function createEvent(
        string $idempotencyKey,
        string $provider,
        string $type,
        array $payload
    ): self {
        return self::create([
            'idempotency_key' => $idempotencyKey,
            'provider' => $provider,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    /**
     * Scope to get events by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get events by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get failed events.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get processed events.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Get event summary for logging.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'idempotency_key' => $this->idempotency_key,
            'provider' => $this->provider,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
