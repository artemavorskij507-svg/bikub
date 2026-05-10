<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RetailStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'kassal_id',
        'name',
        'slug',
        'description',
        'category',
        'chain_name',
        'brand',
        'phone',
        'email',
        'address',
        'city',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'has_home_delivery',
        'delivery_provider',
        'opening_hours',
        'average_delivery_time_minutes',
        'minimum_order_amount',
        'delivery_fee',
        'delivery_currency',
        'metadata',
        'delivery_metadata',
        'supports_grocery_delivery',
        'supports_bulky_delivery',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'has_home_delivery' => 'boolean',
        'supports_grocery_delivery' => 'boolean',
        'supports_bulky_delivery' => 'boolean',
        'opening_hours' => 'array',
        'average_delivery_time_minutes' => 'integer',
        'minimum_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'metadata' => 'array',
        'delivery_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (RetailStore $store) {
            // Автоматическая генерация slug, если он не задан в форме.
            if (empty($store->slug)) {
                $base = Str::slug($store->name ?? 'store');

                if ($base === '') {
                    $base = 'store';
                }

                $slug = $base;
                $suffix = 1;

                // Гарантируем уникальность slug.
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix;
                    $suffix++;
                }

                $store->slug = $slug;
            }
        });
    }

    /**
     * Scope to get only active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get stores by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
