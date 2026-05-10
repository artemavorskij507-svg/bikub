<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'hard_window' => 'boolean',
        'features' => 'array',
        'meta' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(GeoZone::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function employees()
    {
        // Pivot: schedule_slot_employees(slot_id, employee_id, skills, lead)
        // Явно указываем ключи, чтобы не ожидался schedule_slot_id
        return $this->belongsToMany(Employee::class, 'schedule_slot_employees', 'slot_id', 'employee_id')
            ->withPivot(['skills', 'lead']);
    }

    public function orders()
    {
        // Pivot: order_schedule_slot(order_id, slot_id, reservation_status, expires_at)
        // Явно указываем ключи, чтобы не ожидался schedule_slot_id
        return $this->belongsToMany(Order::class, 'order_schedule_slot', 'slot_id', 'order_id')
            ->withPivot(['reservation_status', 'expires_at']);
    }

    protected function capacityFree(): Attribute
    {
        return Attribute::get(function () {
            $total = (int) ($this->capacity_total ?? 0);
            $res = (int) ($this->capacity_reserved ?? 0);
            $conf = (int) ($this->capacity_confirmed ?? 0);

            return max(0, $total - $res - $conf);
        });
    }

    public function scopeActive($q)
    {
        return $q->whereNotIn('status', ['closed']);
    }

    public function isFull(): bool
    {
        $free = $this->capacityFree; // accessor
        $ordersCount = method_exists($this, 'orders') ? $this->orders()->count() : 0;
        $maxOrders = $this->max_orders ?? null;

        return $free <= 0 || ($maxOrders && $ordersCount >= $maxOrders);
    }

    /**
     * Check if the slot is overbooked (booked > capacity).
     */
    public function isOverbooked(): bool
    {
        // Use direct DB fields if available, otherwise calculate from capacity_reserved + capacity_confirmed
        $capacity = (int) ($this->attributes['capacity'] ?? $this->capacity_total ?? 0);
        $booked = (int) ($this->attributes['booked'] ??
                        (($this->capacity_reserved ?? 0) + ($this->capacity_confirmed ?? 0)));

        return $capacity > 0 && $booked > $capacity;
    }

    /**
     * Public helper used by legacy dispatch API.
     */
    public function getAvailableCapacity(): int
    {
        $capacity = (int) ($this->attributes['capacity'] ?? $this->capacity_total ?? 0);
        $booked = (int) ($this->attributes['booked'] ??
            (($this->capacity_reserved ?? 0) + ($this->capacity_confirmed ?? 0)));

        return max(0, $capacity - $booked);
    }

    /**
     * Public helper used by legacy dispatch API.
     */
    public function getOverbookingPercentage(): float
    {
        $capacity = (int) ($this->attributes['capacity'] ?? $this->capacity_total ?? 0);
        if ($capacity <= 0) {
            return 0.0;
        }

        $booked = (int) ($this->attributes['booked'] ??
            (($this->capacity_reserved ?? 0) + ($this->capacity_confirmed ?? 0)));

        if ($booked <= $capacity) {
            return 0.0;
        }

        return round((($booked - $capacity) / $capacity) * 100, 2);
    }

    /**
     * Determine if the slot should be temporarily restricted based on active severe incidents.
     */
    public function shouldBeRestricted(GeoZone $zone): bool
    {
        return TrafficIncident::query()
            ->where('severity', 'severe')
            ->where('status', 'active')
            ->whereBetween('lat', [$zone->center_latitude - 0.1, $zone->center_latitude + 0.1])
            ->whereBetween('lng', [$zone->center_longitude - 0.1, $zone->center_longitude + 0.1])
            ->exists();
    }
}
