<?php

namespace App\Models;

use Clickbar\Magellan\Database\Eloquent\HasPostgisColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoZone extends Model
{
    use HasFactory, HasPostgisColumns;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'geometry',
        'meta',
        'is_active',
        'priority',
        'description',
        'source_file',
        'center_latitude',
        'center_longitude',
        'radius_meters',
        'polygon_coordinates',
        'metadata',
        'org_id',
        'spatial_geometry',
    ];

    protected $casts = [
        'geometry' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'radius_meters' => 'integer',
        'polygon_coordinates' => 'array',
        'metadata' => 'array',
        'org_id' => 'integer',
        'spatial_geometry' => 'array',
    ];

    /**
     * Вказуємо які колонки містять PostGIS дані
     */
    protected $postgisColumns = [
        'spatial_geometry' => \Clickbar\Magellan\Data\Geometries\Geometry::class,
    ];

    /**
     * Get the rules for this geo zone.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(GeoZoneRule::class);
    }

    /**
     * Scope to get only active geo zones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a point is within this geo zone.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->type === 'circle') {
            return $this->containsPointCircle($lat, $lng);
        } elseif ($this->type === 'polygon') {
            return $this->containsPointPolygon($lat, $lng);
        } elseif ($this->type === 'bbox') {
            return $this->containsPointBbox($lat, $lng);
        }

        return false;
    }

    /**
     * Check if point is in circle.
     */
    protected function containsPointCircle(float $lat, float $lng): bool
    {
        $geometry = $this->geometry;

        if (isset($geometry['center']) && isset($geometry['radius_m'])) {
            $center = $geometry['center'];
            $radiusM = $geometry['radius_m'];
        } elseif ($this->center_latitude && $this->center_longitude && $this->radius_meters) {
            $center = [$this->center_latitude, $this->center_longitude];
            $radiusM = $this->radius_meters;
        } else {
            return false;
        }

        $distance = $this->haversineDistance($center[0], $center[1], $lat, $lng);

        return $distance <= $radiusM;
    }

    /**
     * Check if point is in polygon using ray-casting algorithm.
     */
    protected function containsPointPolygon(float $lat, float $lng): bool
    {
        $geometry = $this->geometry;
        $coordinates = null;

        if (isset($geometry['type']) && $geometry['type'] === 'Polygon') {
            $coordinates = $geometry['coordinates'][0] ?? null;
        } elseif ($this->polygon_coordinates) {
            $coordinates = $this->polygon_coordinates;
        }

        if (! $coordinates || count($coordinates) < 3) {
            return false;
        }

        // Ray-casting algorithm
        $inside = false;
        $j = count($coordinates) - 1;

        for ($i = 0; $i < count($coordinates); $i++) {
            $xi = $coordinates[$i][1] ?? $coordinates[$i][0]; // lng
            $yi = $coordinates[$i][0] ?? $coordinates[$i][1]; // lat
            $xj = $coordinates[$j][1] ?? $coordinates[$j][0];
            $yj = $coordinates[$j][0] ?? $coordinates[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat)) &&
                        ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Check if point is in bounding box.
     */
    protected function containsPointBbox(float $lat, float $lng): bool
    {
        $geometry = $this->geometry;

        if (isset($geometry['bbox'])) {
            [$minLng, $minLat, $maxLng, $maxLat] = $geometry['bbox'];
        } else {
            return false;
        }

        return $lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng;
    }

    /**
     * Check if line string intersects this zone.
     */
    public function intersectsLineString(array $lineString): bool
    {
        if (count($lineString) < 2) {
            return false;
        }

        // Check if any segment of the line intersects the zone
        for ($i = 0; $i < count($lineString) - 1; $i++) {
            $p1 = $lineString[$i];
            $p2 = $lineString[$i + 1];

            if ($this->containsPoint($p1[0], $p1[1]) || $this->containsPoint($p2[0], $p2[1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate haversine distance between two points in meters.
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get distance to a point in meters (for backward compatibility).
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        if ($this->center_latitude && $this->center_longitude) {
            return $this->haversineDistance($this->center_latitude, $this->center_longitude, $latitude, $longitude);
        }

        return PHP_FLOAT_MAX;
    }
}
