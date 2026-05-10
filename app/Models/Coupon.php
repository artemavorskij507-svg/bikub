<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'max_uses',
        'used',
        'minimum_order_amount',
        'valid_from',
        'valid_to',
        'applicable_categories',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'applicable_categories' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->valid_from > $now || $this->valid_to < $now) {
            return false;
        }

        if ($this->max_uses && $this->used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isApplicableToOrder(Order $order): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->minimum_order_amount && $order->total_amount < $this->minimum_order_amount) {
            return false;
        }

        if ($this->applicable_categories) {
            $orderCategories = $order->items->pluck('serviceType.category')->unique()->toArray();
            if (! array_intersect($this->applicable_categories, $orderCategories)) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(Order $order): float
    {
        if (! $this->isApplicableToOrder($order)) {
            return 0;
        }

        return match ($this->type) {
            'percent' => $order->total_amount * ($this->value / 100),
            'fixed' => min($this->value, $order->total_amount),
            'free_delivery' => $order->delivery_fee ?? 0,
            'first_order' => $order->user->orders()->count() === 1 ? $this->value : 0,
            default => 0
        };
    }

    public function incrementUsage(): void
    {
        $this->increment('used');
    }
}
