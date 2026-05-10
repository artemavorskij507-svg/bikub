<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'vehicle_id',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'meta' => 'array',
    ];

    /**
     * Get the route stops for this route.
     */
    public function routeStops()
    {
        return $this->hasMany(RouteStop::class)->orderBy('seq');
    }

    /**
     * Get the orders for this route.
     */
    public function orders()
    {
        return $this->hasManyThrough(Order::class, RouteStop::class, 'route_id', 'id', 'id', 'order_id');
    }

    /**
     * Calculate total ETA for the route.
     */
    public function getTotalEtaAttribute()
    {
        $stops = $this->routeStops;
        if ($stops->isEmpty()) {
            return null;
        }

        $lastStop = $stops->last();

        return $lastStop->eta;
    }

    /**
     * Get route efficiency metrics.
     */
    public function getEfficiencyMetrics()
    {
        $stops = $this->routeStops;
        $totalDistance = 0;
        $totalTime = 0;
        $confidenceSum = 0;

        foreach ($stops as $stop) {
            if ($stop->meta) {
                $totalDistance += $stop->meta['distance'] ?? 0;
                $totalTime += $stop->meta['duration'] ?? 0;
                $confidenceSum += $stop->eta_confidence ?? 0;
            }
        }

        return [
            'total_distance' => $totalDistance,
            'total_time' => $totalTime,
            'average_confidence' => $stops->count() > 0 ? $confidenceSum / $stops->count() : 0,
            'stops_count' => $stops->count(),
        ];
    }

    /**
     * Scope to get routes by date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope to get active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }
}
