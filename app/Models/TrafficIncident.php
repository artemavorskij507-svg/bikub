<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id', 'title', 'description', 'severity', 'status', 'starts_at', 'ends_at', 'lat', 'lng', 'geometry', 'meta', 'source_url',
    ];

    protected $casts = [
        'geometry' => 'array',
        'meta' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];
}
