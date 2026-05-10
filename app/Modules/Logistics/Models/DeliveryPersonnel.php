<?php

namespace App\Modules\Logistics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryPersonnel extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'home_warehouse_id',
        'role',
        'status',
        'vehicle_type',
        'vehicle_capacity_kg',
        'max_parcel_count',
        'last_latitude',
        'last_longitude',
        'last_location_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_location_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function shipments(): HasMany { return $this->hasMany(Shipment::class, 'assigned_personnel_id'); }
}

