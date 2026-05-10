<?php

namespace App\Models\Moving;

use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MovingOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        // Автоматически пересчитываем объем и вес при сохранении
        static::saving(function (MovingOrder $order) {
            // Пересчитываем объем и вес из предметов если они есть
            if ($order->exists && $order->items()->exists()) {
                $calculatedVolume = $order->calculateTotalVolume();
                $calculatedWeight = $order->calculateTotalWeight();

                // Обновляем только если не указаны вручную или если изменились предметы
                if (empty($order->total_volume) || $order->isDirty()) {
                    $order->total_volume = $calculatedVolume;
                }
                if (empty($order->total_weight) || $order->isDirty()) {
                    $order->total_weight = $calculatedWeight;
                }
            }

            // Пересчитываем цену если изменились влияющие параметры и цена не указана вручную
            if (empty($order->estimated_price) && $order->exists) {
                if ($order->isDirty(['from_address', 'to_address', 'services', 'package_type', 'total_volume', 'total_weight'])) {
                    try {
                        $order->estimated_price = $order->calculateTotalPrice();
                    } catch (\Exception $e) {
                        \Log::warning('Failed to recalculate price on save', [
                            'order_id' => $order->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        });

        // После сохранения предметов пересчитываем (только для существующих записей)
        static::saved(function (MovingOrder $order) {
            if ($order->exists && $order->items()->exists()) {
                // Используем updateQuietly чтобы избежать рекурсии
                $calculatedVolume = $order->calculateTotalVolume();
                $calculatedWeight = $order->calculateTotalWeight();

                if (abs($order->total_volume - $calculatedVolume) > 0.01 ||
                    abs($order->total_weight - $calculatedWeight) > 0.01) {
                    $order->updateQuietly([
                        'total_volume' => $calculatedVolume,
                        'total_weight' => $calculatedWeight,
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'order_id',
        'status',
        'from_address',
        'to_address',
        'inventory',
        'services',
        'package_type',
        'scheduled_at',
        'executor_team_id',
        'total_volume',
        'total_weight',
        'estimated_price',
        'final_price',
        'estimated_duration_minutes',
        'nps_score',
        'customer_notes',
        'executor_notes',
        'metadata',
    ];

    protected $casts = [
        'from_address' => 'array',
        'to_address' => 'array',
        'inventory' => 'array',
        'services' => 'array',
        'scheduled_at' => 'datetime',
        'total_volume' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'estimated_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'estimated_duration_minutes' => 'integer',
        'nps_score' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this moving order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the executor team assigned to this order.
     */
    public function executorTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'executor_team_id');
    }

    /**
     * Get the items in this moving order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MovingItem::class);
    }

    /**
     * Get the photos for this moving order.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(MovingOrderPhoto::class);
    }

    /**
     * Get pre-move photos.
     */
    public function preMovePhotos(): HasMany
    {
        return $this->photos()->where('collection_name', 'pre_move_photos');
    }

    /**
     * Get related tasks.
     */
    public function relatedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'moving_order_task')
            ->withPivot('task_type')
            ->withTimestamps();
    }

    /**
     * Calculate total volume from items.
     */
    public function calculateTotalVolume(): float
    {
        return $this->items()->sum(DB::raw('volume * quantity'));
    }

    /**
     * Calculate total weight from items.
     */
    public function calculateTotalWeight(): float
    {
        return $this->items()->sum(DB::raw('weight * quantity'));
    }

    /**
     * Calculate total price using MovingPriceCalculator.
     */
    public function calculateTotalPrice(): float
    {
        return Cache::remember("moving:price:{$this->id}", 3600, function () {
            return app(\App\Services\Moving\MovingPriceCalculator::class)->calculate($this);
        });
    }

    /**
     * Calculate disposal volume for eco-services.
     */
    public function calculateDisposalVolume(): float
    {
        // Calculate volume of items that need disposal
        return $this->items()
            ->where('category', 'disposal')
            ->sum(DB::raw('volume * quantity'));
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get confirmed orders.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get in-progress orders.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get formatted from address string.
     */
    public function getFromAddressStringAttribute(): string
    {
        $addr = $this->from_address;
        if (! $addr || ! is_array($addr)) {
            return '—';
        }

        $parts = array_filter([
            $addr['street'] ?? null,
            $addr['postal_code'] ?? null,
            $addr['city'] ?? null,
        ]);

        return implode(', ', $parts) ?: '—';
    }

    /**
     * Get formatted to address string.
     */
    public function getToAddressStringAttribute(): string
    {
        $addr = $this->to_address;
        if (! $addr || ! is_array($addr)) {
            return '—';
        }

        $parts = array_filter([
            $addr['street'] ?? null,
            $addr['postal_code'] ?? null,
            $addr['city'] ?? null,
        ]);

        return implode(', ', $parts) ?: '—';
    }

    /**
     * Get distance between from and to addresses in kilometers.
     */
    public function getDistanceAttribute(): ?float
    {
        $from = $this->from_address;
        $to = $this->to_address;

        if (! $from || ! $to || ! isset($from['lat'], $from['lng'], $to['lat'], $to['lng'])) {
            return null;
        }

        return $this->calculateDistance(
            $from['lat'],
            $from['lng'],
            $to['lat'],
            $to['lng']
        );
    }

    /**
     * Calculate distance between two coordinates (Haversine formula).
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Check if order has specific service enabled.
     */
    public function hasService(string $service): bool
    {
        if (! $this->services || ! is_array($this->services)) {
            return false;
        }

        return isset($this->services[$service]) && $this->services[$service] === true;
    }

    /**
     * Get list of enabled services.
     */
    public function getEnabledServicesAttribute(): array
    {
        if (! $this->services || ! is_array($this->services)) {
            return [];
        }

        return array_keys(array_filter($this->services, fn ($value) => $value === true));
    }

    /**
     * Recalculate and update total volume and weight from items.
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_volume' => $this->calculateTotalVolume(),
            'total_weight' => $this->calculateTotalWeight(),
        ]);
    }

    /**
     * Recalculate and update estimated price.
     */
    public function recalculatePrice(): void
    {
        try {
            $price = $this->calculateTotalPrice();
            $this->update(['estimated_price' => $price]);

            // Also update related Order if exists
            if ($this->order) {
                $this->order->update(['total_amount' => $price]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to recalculate moving order price', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if order can be completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get estimated duration in hours.
     */
    public function getEstimatedDurationHoursAttribute(): ?float
    {
        if (! $this->estimated_duration_minutes) {
            return null;
        }

        return round($this->estimated_duration_minutes / 60, 1);
    }

    /**
     * Scope to filter orders by date range.
     */
    public function scopeScheduledBetween($query, $from, $to)
    {
        return $query->whereBetween('scheduled_at', [$from, $to]);
    }

    /**
     * Scope to filter orders by package type.
     */
    public function scopeByPackageType($query, string $packageType)
    {
        return $query->where('package_type', $packageType);
    }

    /**
     * Scope to get orders with specific service.
     */
    public function scopeWithService($query, string $service)
    {
        return $query->whereJsonContains('services->'.$service, true);
    }

    /**
     * Scope to get orders without assigned team.
     */
    public function scopeWithoutTeam($query)
    {
        return $query->whereNull('executor_team_id');
    }

    /**
     * Scope to get orders with assigned team.
     */
    public function scopeWithTeam($query)
    {
        return $query->whereNotNull('executor_team_id');
    }
}
