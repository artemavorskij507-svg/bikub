<?php

namespace App\Models\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'order_id',
        'type',
        'pickup_location',
        'delivery_location',
        'pickup_address',
        'delivery_address',
        'estimated_distance_km',
        'estimated_duration_minutes',
        'eta',
        'actual_delivery_time',
        'courier_id',
        'courier_location',
        'tracking_status',
        'substitution_policy',
        'is_urgent',
        'metadata',
        'tracking_token',
    ];

    protected $casts = [
        'type' => DeliveryType::class,
        'tracking_status' => DeliveryTrackingStatus::class,
        'substitution_policy' => SubstitutionPolicy::class,
        'pickup_location' => 'array',
        'delivery_location' => 'array',
        'eta' => 'datetime',
        'actual_delivery_time' => 'datetime',
        'courier_location' => 'array',
        'is_urgent' => 'boolean',
        'metadata' => 'array',
        'estimated_distance_km' => 'decimal:2',
        'estimated_duration_minutes' => 'integer',
        'tracking_token' => 'string',
    ];

    /**
     * Get the base order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the polymorphic orderable (GroceryOrder, BulkyOrder, FoodOrder).
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the courier assigned to this delivery.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    /**
     * Get ETA attribute.
     */
    public function getEtaAttribute(): ?Carbon
    {
        if ($this->attributes['eta']) {
            return Carbon::parse($this->attributes['eta']);
        }

        return $this->calculateEta();
    }

    /**
     * Calculate ETA based on location and type.
     */
    protected function calculateEta(): ?Carbon
    {
        if (! $this->pickup_location || ! $this->delivery_location) {
            return null;
        }

        $service = app(\App\Services\Delivery\GeofenceService::class);

        return $service->estimateDeliveryTime(
            $this->pickup_location,
            $this->delivery_location,
            $this->type->value
        );
    }

    /**
     * Check if order is delayed.
     */
    public function isDelayed(): bool
    {
        if (! $this->eta) {
            return false;
        }

        return now()->isAfter($this->eta) && $this->tracking_status !== DeliveryTrackingStatus::DELIVERED;
    }

    /**
     * Get delay in minutes.
     */
    public function delayMinutes(): int
    {
        if (! $this->isDelayed()) {
            return 0;
        }

        return now()->diffInMinutes($this->eta);
    }

    /**
     * Scope for active deliveries.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('tracking_status', [
            DeliveryTrackingStatus::PENDING->value,
            DeliveryTrackingStatus::ASSIGNED->value,
            DeliveryTrackingStatus::PICKED_UP->value,
            DeliveryTrackingStatus::IN_TRANSIT->value,
        ]);
    }

    /**
     * Scope for type.
     */
    public function scopeOfType(Builder $query, DeliveryType|string $type): Builder
    {
        $typeValue = $type instanceof DeliveryType ? $type->value : $type;

        return $query->where('type', $typeValue);
    }

    protected static function newFactory(): DeliveryOrderFactory
    {
        return DeliveryOrderFactory::new();
    }
}
