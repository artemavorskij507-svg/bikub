<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
        'tier',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(LoyaltyLedger::class, 'wallet_id');
    }

    public function addPoints(int $points, string $reason, ?string $orderId = null): void
    {
        $this->balance += $points;
        $this->total_earned += $points;

        $this->ledger()->create([
            'delta' => $points,
            'type' => 'earn',
            'reason' => $reason,
            'order_id' => $orderId,
        ]);

        $this->updateTier();
        $this->save();
    }

    public function spendPoints(int $points, string $reason, ?string $orderId = null): bool
    {
        if ($this->balance < $points) {
            return false;
        }

        $this->balance -= $points;
        $this->total_spent += $points;

        $this->ledger()->create([
            'delta' => -$points,
            'type' => 'spend',
            'reason' => $reason,
            'order_id' => $orderId,
        ]);

        $this->updateTier();
        $this->save();

        return true;
    }

    private function updateTier(): void
    {
        $this->tier = match (true) {
            $this->total_earned >= 10000 => 'platinum',
            $this->total_earned >= 5000 => 'gold',
            $this->total_earned >= 1000 => 'silver',
            default => 'bronze'
        };
    }

    public function getTierMultiplier(): float
    {
        return match ($this->tier) {
            'platinum' => 1.5,
            'gold' => 1.3,
            'silver' => 1.1,
            default => 1.0
        };
    }
}
