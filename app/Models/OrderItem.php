<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_type_id',
        'product_id',
        'store_id',
        'pricing_rule_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'currency',
        'options',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'options' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the order that owns this order item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the service type for this order item.
     */
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * Get the pricing rule used for this order item.
     */
    public function pricingRule()
    {
        return $this->belongsTo(PricingRule::class);
    }

    /**
     * Calculate the total price based on quantity and unit price.
     */
    public function calculateTotalPrice(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
    }
}
