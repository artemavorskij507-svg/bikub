<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'return_id',
        'order_item_id',
        'product_id',
        'quantity',
        'price_at_return',
        'reason',
        'condition', // e.g., 'new', 'used', 'damaged'
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'price_at_return' => 'decimal:2',
    ];

    /**
     * Get the return that owns the item.
     */
    public function return(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'return_id');
    }

    /**
     * Get the original order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
