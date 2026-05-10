<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'points_amount',
        'description',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'points_amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    /**
     * Отримати текстовий опис типу транзакції
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'earn' => 'Earned Points',
            'redeem' => 'Redeemed Points',
            'manual_add' => 'Manual Addition',
            'manual_remove' => 'Manual Removal',
            'expire' => 'Expired Points',
            'admin_adjustment' => 'Admin Adjustment',
            default => ucfirst($this->type),
        };
    }

    /**
     * Отримати иконку для типу транзакції
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'earn' => 'heroicon-o-arrow-up',
            'redeem' => 'heroicon-o-arrow-down',
            'manual_add' => 'heroicon-o-plus-circle',
            'manual_remove' => 'heroicon-o-minus-circle',
            'expire' => 'heroicon-o-clock',
            'admin_adjustment' => 'heroicon-o-cog',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Отримати колір для типу транзакції
     */
    public function getTypeColor(): string
    {
        return match ($this->type) {
            'earn' => 'success',
            'redeem' => 'warning',
            'manual_add' => 'info',
            'manual_remove' => 'danger',
            'expire' => 'secondary',
            'admin_adjustment' => 'primary',
            default => 'gray',
        };
    }
}
