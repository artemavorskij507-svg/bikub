<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoZoneRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'geo_zone_id',
        'key',
        'value',
        'description',
        'active',
    ];

    protected $casts = [
        'value' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Get the geo zone that owns this rule.
     */
    public function geoZone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class);
    }

    /**
     * Scope to get only active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
