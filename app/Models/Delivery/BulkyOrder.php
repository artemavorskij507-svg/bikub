<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkyOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bulky_orders';

    protected $fillable = [
        'dimensions',
        'weight_kg',
        'services',
        'requires_assembly',
        'requires_disassembly',
        'floor_number',
        'elevator_available',
        'notes',
    ];

    protected $casts = [
        'dimensions' => 'array', // {length, width, height, volume}
        'weight_kg' => 'decimal:2',
        'services' => 'array', // ['assembly', 'disassembly', 'packaging', etc.]
        'requires_assembly' => 'boolean',
        'requires_disassembly' => 'boolean',
        'elevator_available' => 'boolean',
        'floor_number' => 'integer',
    ];

    /**
     * Get the delivery order.
     */
    public function deliveryOrder(): MorphOne
    {
        return $this->morphOne(DeliveryOrder::class, 'orderable');
    }

    /**
     * Get base price attribute.
     */
    public function getBasePriceAttribute(): float
    {
        $calculator = app(\App\Services\Delivery\TariffCalculator::class);

        $pickupLocation = [];
        if ($this->deliveryOrder) {
            $pickupLocation = $this->deliveryOrder->pickup_location ?? [];
        }

        return $calculator->calculateForBulky(
            $this->dimensions ?? [],
            $this->services ?? [],
            $pickupLocation
        );
    }

    /**
     * Calculate volume in cubic meters.
     */
    public function getVolumeAttribute(): float
    {
        if (! $this->dimensions || ! isset($this->dimensions['length'], $this->dimensions['width'], $this->dimensions['height'])) {
            return 0;
        }

        return ($this->dimensions['length'] * $this->dimensions['width'] * $this->dimensions['height']) / 1000000; // cm³ to m³
    }
}
