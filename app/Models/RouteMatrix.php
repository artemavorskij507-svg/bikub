<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteMatrix extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_address',
        'to_address',
        'from_lat',
        'from_lng',
        'to_lat',
        'to_lng',
        'distance_meters',
        'duration_seconds',
        'mode',
        'route_data',
        'cached_at',
    ];

    protected $casts = [
        'route_data' => 'array',
        'from_lat' => 'decimal:8',
        'from_lng' => 'decimal:8',
        'to_lat' => 'decimal:8',
        'to_lng' => 'decimal:8',
        'cached_at' => 'datetime',
    ];
}
