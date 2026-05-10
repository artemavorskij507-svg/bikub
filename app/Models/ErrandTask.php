<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use App\Services\Errand\ErrandPricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrandTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'title',
        'category',
        'sub_category',
        'description',
        'status',
        'priority',
        'customer_name',
        'customer_phone',
        'pickup_address',
        'dropoff_address',
        'pickup_location',
        'dropoff_location',
        'from_address',
        'from_location',
        'to_address',
        'to_location',
        'waypoints',
        'via_points',
        'contacts',
        'notes',
        'is_urgent',
        'requires_signature',
        'requires_trusted_helper',
        'requires_document_handling',
        'expected_duration_minutes',
        'expected_distance_km',
        'complexity_level',
        'risk_score',
        'material_advance_amount',
        'base_fee',
        'distance_fee',
        'time_fee',
        'complexity_fee',
        'trusted_helper_fee',
        'urgency_fee',
        'estimated_total_amount',
        'final_total_amount',
        'executor_profile_id',
        'geo_zone_id',
        'scheduled_at',
        'completed_at',
        'pricing_snapshot',
        'meta',
    ];

    protected $casts = [
        'pickup_location' => 'array',
        'dropoff_location' => 'array',
        'from_location' => 'array',
        'to_location' => 'array',
        'waypoints' => 'array',
        'via_points' => 'array',
        'contacts' => 'array',
        'is_urgent' => 'boolean',
        'requires_signature' => 'boolean',
        'requires_trusted_helper' => 'boolean',
        'requires_document_handling' => 'boolean',
        'expected_distance_km' => 'decimal:2',
        'material_advance_amount' => 'integer',
        'base_fee' => 'decimal:2',
        'distance_fee' => 'decimal:2',
        'time_fee' => 'decimal:2',
        'complexity_fee' => 'decimal:2',
        'trusted_helper_fee' => 'decimal:2',
        'urgency_fee' => 'decimal:2',
        'estimated_total_amount' => 'decimal:2',
        'final_total_amount' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'pricing_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function executorProfile(): BelongsTo
    {
        return $this->belongsTo(ExecutorProfile::class);
    }

    public function geoZone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function refreshPricing(?float $distanceKm = null): self
    {
        $details = $this->order?->errandDetails;

        if (! $details) {
            return $this;
        }

        $pricingService = app(ErrandPricingService::class);

        $estimate = $pricingService->estimate(
            $details,
            $distanceKm ?? (float) ($this->expected_distance_km ?? 0)
        );

        $details->fill($estimate)->save();

        $this->pricing_snapshot = $estimate;
        $this->save();

        return $this;
    }
}
