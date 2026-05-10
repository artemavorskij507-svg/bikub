<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cuisine_type',
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
        'has_takeaway',
        'delivery_provider',
        'opening_hours',
        'average_delivery_time_minutes',
        'minimum_order_amount',
        'delivery_fee',
        'delivery_currency',
        'metadata',
        'delivery_metadata',
        'supports_food_delivery',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'has_home_delivery' => 'boolean',
        'has_takeaway' => 'boolean',
        'supports_food_delivery' => 'boolean',
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

        static::creating(function (Restaurant $restaurant) {
            // Автоматическая генерация slug, если он не задан в форме.
            if (empty($restaurant->slug)) {
                $base = Str::slug($restaurant->name ?? 'restaurant');

                if ($base === '') {
                    $base = 'restaurant';
                }

                $slug = $base;
                $suffix = 1;

                // Гарантируем уникальность slug.
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix;
                    $suffix++;
                }

                $restaurant->slug = $slug;
            }
        });
    }

    /**
     * Scope to get only active restaurants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get restaurants with home delivery.
     */
    public function scopeWithHomeDelivery($query)
    {
        return $query->where('has_home_delivery', true);
    }
}
