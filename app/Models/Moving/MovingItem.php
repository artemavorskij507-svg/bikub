<?php

namespace App\Models\Moving;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'moving_order_id',
        'name',
        'category',
        'volume',
        'weight',
        'requires_assembly',
        'is_fragile',
        'quantity',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'weight' => 'decimal:2',
        'requires_assembly' => 'boolean',
        'is_fragile' => 'boolean',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the moving order that owns this item.
     */
    public function movingOrder(): BelongsTo
    {
        return $this->belongsTo(MovingOrder::class);
    }

    /**
     * Calculate total volume for this item (volume * quantity).
     */
    public function getTotalVolumeAttribute(): float
    {
        return $this->volume * $this->quantity;
    }

    /**
     * Calculate total weight for this item (weight * quantity).
     */
    public function getTotalWeightAttribute(): float
    {
        return $this->weight * $this->quantity;
    }
}
