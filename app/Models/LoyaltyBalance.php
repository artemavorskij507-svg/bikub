<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points',
        'lifetime_points',
    ];

    protected $casts = [
        'points' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Додати бали користувачу
     */
    public function addPoints(int $amount, ?string $description = null, ?string $sourceType = null, ?int $sourceId = null): LoyaltyTransaction
    {
        $this->increment('points', $amount);
        $this->increment('lifetime_points', $amount);

        return LoyaltyTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'earn',
            'points_amount' => $amount,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    /**
     * Списати баліс користувача
     */
    public function redeemPoints(int $amount, ?string $description = null): bool
    {
        if ($this->points < $amount) {
            return false;
        }

        $this->decrement('points', $amount);

        LoyaltyTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'redeem',
            'points_amount' => -$amount,
            'description' => $description,
        ]);

        return true;
    }

    /**
     * Перевірити наявність достатньої кількості балів
     */
    public function hasEnoughPoints(int $amount): bool
    {
        return $this->points >= $amount;
    }

    /**
     * Отримати відсоток від вартості замовлення
     */
    public function getPointsValue(int $points, float $pointValue = 0.01): float
    {
        return $points * $pointValue;
    }
}
