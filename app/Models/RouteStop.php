<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'order_id',
        'seq',
        'eta',
        'eta_confidence',
        'meta',
    ];

    protected $casts = [
        'eta' => 'datetime',
        'eta_confidence' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get the route for this stop.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the order for this stop.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the next stop in sequence.
     */
    public function nextStop()
    {
        return $this->route->routeStops()
            ->where('seq', '>', $this->seq)
            ->orderBy('seq')
            ->first();
    }

    /**
     * Get the previous stop in sequence.
     */
    public function previousStop()
    {
        return $this->route->routeStops()
            ->where('seq', '<', $this->seq)
            ->orderBy('seq', 'desc')
            ->first();
    }

    /**
     * Calculate distance to next stop.
     */
    public function getDistanceToNext()
    {
        $nextStop = $this->nextStop();
        if (! $nextStop) {
            return null;
        }

        $currentLocation = $this->order->location ?? [];
        $nextLocation = $nextStop->order->location ?? [];

        if (empty($currentLocation) || empty($nextLocation)) {
            return null;
        }

        return $this->calculateDistance(
            $currentLocation['lat'],
            $currentLocation['lng'],
            $nextLocation['lat'],
            $nextLocation['lng']
        );
    }

    /**
     * Calculate ETA confidence based on various factors.
     */
    public function calculateEtaConfidence()
    {
        $confidence = 0.8; // Base confidence

        // Reduce confidence for longer distances
        $distance = $this->getDistanceToNext();
        if ($distance) {
            if ($distance > 10) {
                $confidence -= 0.2;
            } elseif ($distance > 5) {
                $confidence -= 0.1;
            }
        }

        // Reduce confidence for complex orders
        $orderItemsCount = $this->order->orderItems->count();
        if ($orderItemsCount > 3) {
            $confidence -= 0.1;
        }

        // Weather factor (if available in meta)
        if (isset($this->meta['weather_factor'])) {
            $confidence -= $this->meta['weather_factor'] * 0.1;
        }

        return max(0.1, min(1.0, $confidence));
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scope to get stops by sequence.
     */
    public function scopeBySequence($query, $routeId)
    {
        return $query->where('route_id', $routeId)->orderBy('seq');
    }

    /**
     * Scope to get stops with ETA.
     */
    public function scopeWithEta($query)
    {
        return $query->whereNotNull('eta');
    }
}
