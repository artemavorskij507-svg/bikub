<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FoodOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'food_orders';

    protected $fillable = [
        'restaurant_id',
        'items',
        'special_instructions',
        'temperature_requirements',
        'allergen_info',
    ];

    protected $casts = [
        'items' => 'array',
        'temperature_requirements' => 'array',
        'allergen_info' => 'array',
    ];

    /**
     * Get the delivery order.
     */
    public function deliveryOrder(): MorphOne
    {
        return $this->morphOne(DeliveryOrder::class, 'orderable');
    }

    /**
     * Get the restaurant.
     */
    public function restaurant()
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }
}
