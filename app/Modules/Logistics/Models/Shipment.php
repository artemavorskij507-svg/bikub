<?php

namespace App\Modules\Logistics\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'order_id',
        'delivery_order_id',
        'service_type_id',
        'pricing_rule_id',
        'sender_user_id',
        'recipient_user_id',
        'origin_address_id',
        'destination_address_id',
        'current_route_id',
        'current_geo_zone_id',
        'assigned_personnel_id',
        'status',
        'priority',
        'parcel_count',
        'total_weight_kg',
        'total_volume_m3',
        'declared_value',
        'currency',
        'promised_delivery_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'external_reference',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'promised_delivery_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function parcels(): HasMany { return $this->hasMany(Parcel::class); }
    public function trackingEvents(): HasMany { return $this->hasMany(TrackingEvent::class); }
    public function route(): BelongsTo { return $this->belongsTo(DeliveryRoute::class, 'current_route_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(DeliveryPersonnel::class, 'assigned_personnel_id'); }
    public function originAddress(): BelongsTo { return $this->belongsTo(CustomerAddress::class, 'origin_address_id'); }
    public function destinationAddress(): BelongsTo { return $this->belongsTo(CustomerAddress::class, 'destination_address_id'); }

    public function scopeActive(Builder $query): Builder { return $query->whereNotIn('status', ['delivered', 'cancelled']); }
    public function scopeForTracking(Builder $query, string $trackingNumber): Builder { return $query->where('shipment_number', $trackingNumber); }
}
