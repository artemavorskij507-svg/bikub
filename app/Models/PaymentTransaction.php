<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'currency',
        'amount_minor',
        'provider',
        'provider_payment_id',
        'provider_charge_id',
        'status',
        'label',
        'meta',
        'processed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    protected $appends = ['amount'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_minor / 100;
    }
}
