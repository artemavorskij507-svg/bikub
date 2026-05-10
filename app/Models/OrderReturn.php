<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'type', // e.g., 'return', 'exchange'
        'reason',
        'status', // e.g., 'pending', 'approved', 'rejected', 'completed'
        'items', // JSON array of items returned with their details (e.g., product_id, quantity, price_at_return)
        'restocking_fee',
        'notes',
        'processed_by',
        'processed_at',
        'meta', // For any additional flexible data
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'meta' => 'array',
        'restocking_fee' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the order that the return belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who processed the return.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the individual items associated with the return.
     * It's generally better to use a dedicated relationship for return items
     * rather than storing them as a JSON array in the 'items' column.
     * This provides better data integrity and querying.
     */
    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
