<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoadHelperProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_model',
        'vehicle_number',
        'equipment',
        'skills',
        'current_status',
        'location_lat',
        'location_lng',
        'metadata',
    ];

    protected $casts = [
        'equipment' => 'array',
        'skills' => 'array',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get active orders assigned to this helper.
     */
    public function activeOrders(): HasMany
    {
        return $this->hasMany(RoadsideEmergency::class, 'road_helper_id')
            ->whereIn('status', ['assigned', 'on_route', 'in_progress']);
    }

    /**
     * Get all emergencies assigned to this helper.
     */
    public function emergencies(): HasMany
    {
        return $this->hasMany(RoadsideEmergency::class, 'road_helper_id');
    }

    /**
     * Get all inspection requests assigned to this helper.
     */
    public function inspectionRequests(): HasMany
    {
        return $this->hasMany(VehicleInspectionRequest::class, 'assigned_helper_id');
    }

    /**
     * Scope for available helpers.
     */
    public function scopeAvailable($query)
    {
        // Перевіряємо current_status = 'idle' (доступний) або 'on_route' (в дорозі, але може взяти ще)
        return $query->where(function ($q) {
            $q->whereIn('current_status', ['idle', 'on_route'])
                ->orWhereNull('current_status');
        });
    }
}
