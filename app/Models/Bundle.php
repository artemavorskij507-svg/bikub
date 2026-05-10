<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'items',
        'base_price',
        'discount_percent',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function bundleOrders(): HasMany
    {
        return $this->hasMany(BundleOrder::class);
    }

    public function calculateDiscountedPrice(): float
    {
        return $this->base_price * (1 - $this->discount_percent / 100);
    }

    public function getDiscountAmount(): float
    {
        return $this->base_price * ($this->discount_percent / 100);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
