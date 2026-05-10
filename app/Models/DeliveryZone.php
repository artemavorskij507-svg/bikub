<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $table = 'delivery_zones';

    protected $fillable = [
        'name',
        'type',
        'center_lat',
        'center_lng',
        'radius_km',
        'geometry_data',
        'coordinates',
        'is_active',
        'delivery_fee',
        'delivery_time_minutes',
    ];

    protected $casts = [
        'geometry_data' => 'array',
        'coordinates' => 'array',
        'is_active' => 'boolean',
        'center_lat' => 'float',
        'center_lng' => 'float',
        'radius_km' => 'float',
        'delivery_fee' => 'float',
    ];

    /**
     * Отримати всі замовлення доставки в цій зоні
     */
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'delivery_zone_id');
    }

    /**
     * Перевірити, чи точка знаходиться в зоні
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        // Простий радіус-тест для круглих зон
        if ($this->type === 'circle' && $this->radius_km) {
            $distance = $this->calculateDistance($lat, $lng);

            return $distance <= $this->radius_km;
        }

        // Для полігонів можна використовувати координати
        if ($this->type === 'polygon' && $this->coordinates) {
            return $this->pointInPolygon($lat, $lng, $this->coordinates);
        }

        return false;
    }

    /**
     * Розраховує відстань між двома точками (Haversine formula)
     */
    private function calculateDistance(float $lat, float $lng): float
    {
        $lat1 = $this->center_lat;
        $lon1 = $this->center_lng;
        $lat2 = $lat;
        $lon2 = $lng;

        $earth_radius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        return $distance;
    }

    /**
     * Перевіряє, чи точка знаходиться всередині полігону (Ray casting algorithm)
     */
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $p1x = 0;
        $p1y = 0;
        $n = count($polygon);

        for ($i = 0; $i < $n; $i++) {
            $p2x = $polygon[$i][0];
            $p2y = $polygon[$i][1];

            if ($i === 0) {
                $p1x = $p2x;
                $p1y = $p2y;

                continue;
            }

            if ($lng > min($p1y, $p2y)) {
                if ($lng <= max($p1y, $p2y)) {
                    if ($lat <= max($p1x, $p2x)) {
                        if ($p1y !== $p2y) {
                            $xinters = ($lng - $p1y) * ($p2x - $p1x) / ($p2y - $p1y) + $p1x;
                        }
                        if ($p1x === $p2x || $lat <= $xinters) {
                            $inside = ! $inside;
                        }
                    }
                }
            }
            $p1x = $p2x;
            $p1y = $p2y;
        }

        return $inside;
    }

    /**
     * Отримати вартість доставки для координат
     */
    public static function getDeliveryFeeForLocation(float $lat, float $lng): ?float
    {
        $zone = self::where('is_active', true)
            ->get()
            ->first(fn ($zone) => $zone->containsPoint($lat, $lng));

        return $zone?->delivery_fee;
    }

    /**
     * Отримати час доставки для координат
     */
    public static function getDeliveryTimeForLocation(float $lat, float $lng): ?int
    {
        $zone = self::where('is_active', true)
            ->get()
            ->first(fn ($zone) => $zone->containsPoint($lat, $lng));

        return $zone?->delivery_time_minutes;
    }
}
