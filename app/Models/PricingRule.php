<?php

namespace App\Models;

use App\Services\Pricing\OrderContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type_id',
        'service_type',
        'geo_zone_id',
        'name',
        'slug',
        'type',
        'description',
        'value',
        'currency',
        'unit',
        'applies_to',
        'conditions',
        'priority',
        'active',
        'meta',
        'base_price',
        'base_fee',
        'per_km_fee',
        'per_m3_fee',
        'per_kg_fee',
        'urgency_multiplier',
        'night_multiplier',
        'pricing_model',
        'modifiers',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'base_price' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'per_m3_fee' => 'decimal:2',
        'per_kg_fee' => 'decimal:2',
        'urgency_multiplier' => 'decimal:2',
        'night_multiplier' => 'decimal:2',
        'pricing_model' => 'array',
        'conditions' => 'array',
        'applies_to' => 'array',
        'modifiers' => 'array',
        'meta' => 'array',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->slug && $model->name) {
                $model->slug = Str::slug($model->name) ?: (string) Str::uuid();
            }
        });

        $flush = fn () => Cache::forget('pricing:active_rules');

        static::saved($flush);
        static::deleted($flush);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function geoZone()
    {
        return $this->belongsTo(GeoZone::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('active', true)
                ->orWhere('is_active', true);
        });
    }

    public function scopeValidFor($query, $date = null)
    {
        $date = $date ?? now();

        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date);
        });
    }

    public function appliesToContext(OrderContext $context): bool
    {
        $applies = $this->applies_to ?? [];

        if (isset($applies['service_types']) && ! in_array($context->serviceType, $applies['service_types'], true)) {
            return false;
        }

        if ($context->category && isset($applies['categories']) && ! in_array($context->category, $applies['categories'], true)) {
            return false;
        }

        if ($context->zone && isset($applies['zones']) && ! in_array($context->zone, $applies['zones'], true)) {
            return false;
        }

        return true;
    }
}
