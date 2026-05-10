<?php

namespace App\Models\Delivery;

use App\Enums\SubstitutionPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroceryItem extends Model
{
    use HasFactory;

    protected $table = 'grocery_items';

    protected $fillable = [
        'grocery_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'substitution_policy',
        'substitution_proposed',
        'notes',
    ];

    protected $casts = [
        'substitution_policy' => SubstitutionPolicy::class,
        'substitution_proposed' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the grocery order.
     */
    public function groceryOrder(): BelongsTo
    {
        return $this->belongsTo(GroceryOrder::class);
    }

    /**
     * Get the product.
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
