<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_code',
        'service_type_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'assigned_personnel_id',
        'status',
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',
        'estimated_distance_km',
        'estimated_duration_minutes',
        'waypoints',
        'metadata',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'waypoints' => 'array',
        'metadata' => 'array',
    ];

    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function scopeActive(Builder $query): Builder { return $query->whereIn('status', ['planned','in_progress']); }
}
