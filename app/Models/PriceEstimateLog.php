<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceEstimateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'service_type',
        'zone',
        'currency',
        'user_id',
        'request_hash',
        'payload',
        'result',
        'subtotal',
        'total',
        'duration_ms',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
