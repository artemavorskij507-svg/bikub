<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Models\Tenant;

class Partner extends Tenant
{
    use HasFactory;

    const TYPE_TOWING_SERVICE = 'towing_service';

    const TYPE_SERVICE_STATION = 'service_station';

    const TYPE_AUTOSERVICE = 'autoservice';

    const TYPE_INSPECTION_EXPERT = 'inspection_expert';

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'vat_registered' => 'boolean',
        'payout_terms' => 'array',
        'flags' => 'array',
        'capabilities' => 'array',
        'service_area' => 'array',
        'metadata' => 'array',
        'contract_valid_to' => 'datetime',
        'on_time_rate' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'emergency_price_base' => 'decimal:2',
        'emergency_price_per_km' => 'decimal:2',
        'priority' => 'integer',
    ];

    public function contacts()
    {
        return $this->hasMany(PartnerContact::class);
    }

    public function contracts()
    {
        return $this->hasMany(PartnerContract::class);
    }

    public function zones()
    {
        return $this->belongsToMany(GeoZone::class, 'geo_zone_partner')->withPivot(['window']);
    }

    public function services()
    {
        return $this->belongsToMany(ServiceType::class, 'partner_service_type')->withPivot(['base_fee_cents', 'per_km_cents', 'sla_minutes', 'is_active']);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the primary geo zone for this partner.
     */
    public function geoZone()
    {
        return $this->belongsTo(GeoZone::class);
    }

    /**
     * Scope for roadside partners.
     */
    public function scopeRoadside($query)
    {
        return $query->whereIn('type', [
            self::TYPE_TOWING_SERVICE,
            'roadside_mobile',
            'repair_shop',
            'inspection_center',
            self::TYPE_SERVICE_STATION,
            self::TYPE_AUTOSERVICE,
            self::TYPE_INSPECTION_EXPERT,
        ]);
    }

    public function scopeActive($q)
    {
        // Перевіряємо обидва поля для сумісності
        return $q->where(function ($query) {
            $query->where('active', true)
                ->orWhere('is_active', true);
        });
    }

    /**
     * Scope for available partners (active and available).
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->where('active', true)
                ->orWhere('is_active', true);
        })->where(function ($q) {
            $q->where('is_available', true)
                ->orWhereNull('is_available');
        });
    }
}
