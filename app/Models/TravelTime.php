<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id', 'route_name', 'from_location', 'to_location',
        'from_lat', 'from_lng', 'to_lat', 'to_lng',
        'travel_time_seconds', 'distance_meters', 'average_speed_kmh',
        'status', 'measured_at', 'geometry', 'meta', 'source_url',
    ];

    protected $casts = [
        'geometry' => 'array',
        'meta' => 'array',
        'measured_at' => 'datetime',
        'from_lat' => 'decimal:8',
        'from_lng' => 'decimal:8',
        'to_lat' => 'decimal:8',
        'to_lng' => 'decimal:8',
        'average_speed_kmh' => 'decimal:2',
    ];

    // Calculate average speed from time and distance
    public function calculateSpeed(): ?float
    {
        if ($this->travel_time_seconds && $this->distance_meters && $this->travel_time_seconds > 0) {
            $hours = $this->travel_time_seconds / 3600;
            $km = $this->distance_meters / 1000;

            return $hours > 0 ? round($km / $hours, 2) : null;
        }

        return null;
    }
}
