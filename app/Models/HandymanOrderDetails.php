<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandymanOrderDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'handyman_service_id',
        'is_custom_request',
        'description',
        'context_notes',
        'needs_materials_purchase',
        'materials_notes',
        'expected_duration_minutes',
        'address_line',
        'postal_code',
        'city',
        'attachments',
        'estimated_price_minor',
        'final_price_minor',
        'desired_start_at',
        'desired_finish_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_custom_request' => 'boolean',
        'needs_materials_purchase' => 'boolean',
        'desired_start_at' => 'datetime',
        'desired_finish_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function handymanService(): BelongsTo
    {
        return $this->belongsTo(HandymanService::class);
    }

    public function getEstimatedPriceAttribute(): ?float
    {
        return $this->estimated_price_minor
            ? $this->estimated_price_minor / 100
            : null;
    }

    public function getFinalPriceAttribute(): ?float
    {
        return $this->final_price_minor
            ? $this->final_price_minor / 100
            : null;
    }
}
