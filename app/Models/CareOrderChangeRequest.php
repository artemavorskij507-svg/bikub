<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareOrderChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'requested_by_user_id',
        'requested_new_start_at',
        'requested_new_end_at',
        'reason',
        'status',
        'metadata',
    ];

    protected $casts = [
        'requested_new_start_at' => 'datetime',
        'requested_new_end_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
