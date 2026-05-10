<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'service_category_id',
        'name',
        'slug',
        'description',
        'category',
        'icon',
        'features',
        'default_pricing',
        'skills',
        'inventory',
        'estimated_duration_minutes',
        'is_active',
        'sort_order',
        'org_id',
        'canonical_code',
        'default_pricing_group',
    ];

    protected $casts = [
        'features' => 'array',
        'default_pricing' => 'array',
        'skills' => 'array',
        'inventory' => 'array',
        'is_active' => 'boolean',
        'org_id' => 'string',
    ];

    /**
     * Get the service category for this service type.
     */
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * Backward‑compat alias used by some controllers (e.g. PublicStorefrontController).
     */
    public function category()
    {
        return $this->serviceCategory();
    }

    /**
     * Get the pricing rules for this service type.
     */
    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }

    /**
     * Get the order items for this service type.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope to get only active service types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to get service types by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
