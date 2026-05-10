<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GeoZoneSpatial extends Model
{
    use HasFactory;

    protected $table = 'geo_zones';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'geometry',
        'spatial_geometry',
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
    ];

    protected $casts = [
        'geometry' => 'array',
        'spatial_geometry' => 'string', // Will be cast from PostGIS geometry
        'meta' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'radius_meters' => 'integer',
        'polygon_coordinates' => 'array',
        'metadata' => 'array',
        'org_id' => 'integer',
    ];

    /**
     * Scope to find zones containing a point using PostGIS spatial queries.
     */
    public function scopeContainsPoint($query, float $latitude, float $longitude)
    {
        return $query->whereRaw('ST_Contains(spatial_geometry, ST_GeomFromText(?, 4326))', [
            "POINT({$longitude} {$latitude})",
        ]);
    }

    /**
     * Scope to find zones within a certain distance from a point.
     */
    public function scopeWithinDistance($query, float $latitude, float $longitude, float $distanceInMeters)
    {
        return $query->whereRaw('ST_DWithin(spatial_geometry, ST_GeomFromText(?, 4326), ?)', [
            "POINT({$longitude} {$latitude})",
            $distanceInMeters,
        ]);
    }

    /**
     * Get the distance to a point using PostGIS.
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        $result = DB::selectOne(
            'SELECT ST_Distance(spatial_geometry, ST_GeomFromText(?, 4326)) as distance',
            ["POINT({$longitude} {$latitude})"]
        );

        return $result ? $result->distance : PHP_FLOAT_MAX;
    }

    /**
     * Update spatial geometry from polygon coordinates.
     */
    public function updateSpatialGeometry(array $coordinates): bool
    {
        if (count($coordinates) < 3) {
            return false;
        }

        // Create POLYGON WKT string
        $coordinatesString = implode(',', array_map(function ($coord) {
            $lng = $coord[1] ?? $coord[0];
            $lat = $coord[0] ?? $coord[1];

            return "{$lng} {$lat}";
        }, $coordinates));

        // Close the polygon by adding the first point again
        $firstCoord = $coordinates[0];
        $firstLng = $firstCoord[1] ?? $firstCoord[0];
        $firstLat = $firstCoord[0] ?? $firstCoord[1];
        $coordinatesString .= ",{$firstLng} {$firstLat}";

        $polygonWkt = "POLYGON(($coordinatesString))";

        return $this->update([
            'spatial_geometry' => DB::raw("ST_GeomFromText('{$polygonWkt}', 4326)"),
        ]);
    }

    /**
     * Get geometry as GeoJSON.
     */
    public function getGeometryAsGeoJSON(): ?string
    {
        $result = DB::selectOne('SELECT ST_AsGeoJSON(spatial_geometry) as geojson WHERE id = ?', [$this->id]);

        return $result ? $result->geojson : null;
    }

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
}
