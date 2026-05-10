<?php

namespace App\Models\Delivery;

use App\Enums\SubstitutionPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grocery_orders';

    protected $fillable = [
        'substitution_policy',
        'is_urgent',
        'store_id',
        'preferred_delivery_window',
        'notes',
    ];

    protected $casts = [
        'substitution_policy' => SubstitutionPolicy::class,
        'preferred_delivery_window' => 'array',
        'is_urgent' => 'boolean',
    ];

    /**
     * Get the delivery order.
     */
    public function deliveryOrder(): MorphOne
    {
        return $this->morphOne(DeliveryOrder::class, 'orderable');
    }

    /**
     * Get grocery items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GroceryItem::class);
    }

    /**
     * Get the retail store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RetailStore::class, 'store_id');
    }

    /**
     * Suggest substitutions for out-of-stock items.
     */
    public function suggestSubstitutions()
    {
        \App\Jobs\Delivery\SubstitutionJob::dispatch($this)
            ->onQueue('ai-processing');
    }
}
