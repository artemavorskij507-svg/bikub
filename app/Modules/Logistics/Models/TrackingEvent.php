<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shipment_id',
        'parcel_id',
        'route_id',
        'warehouse_id',
        'personnel_id',
        'customer_address_id',
        'event_type',
        'event_status',
        'event_time',
        'source_system',
        'source_event_id',
        'latitude',
        'longitude',
        'payload',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'payload' => 'array',
    ];

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}

