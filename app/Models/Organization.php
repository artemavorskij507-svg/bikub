<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'domain',
        'subdomain',
        'description',
        'logo_url',
        'branding',
        'features',
        'settings',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'branding' => 'array',
        'features' => 'array',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot(['role', 'permissions', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'org_id');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class, 'org_id');
    }

    public function serviceTypes(): HasMany
    {
        return $this->hasMany(ServiceType::class, 'org_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'org_id');
    }

    public function geoZones(): HasMany
    {
        return $this->hasMany(GeoZone::class, 'org_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'org_id');
    }

    public function searchIndexes(): HasMany
    {
        return $this->hasMany(SearchIndex::class, 'org_id');
    }

    public function searchSynonyms(): HasMany
    {
        return $this->hasMany(SearchSynonym::class, 'org_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at > now();
    }

    public function isTrialExpired(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at <= now();
    }

    public function getBranding(): array
    {
        return $this->branding ?? [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'logo_url' => $this->logo_url,
            'font_family' => 'Inter',
        ];
    }

    public function getFeatures(): array
    {
        return $this->features ?? [
            'subscriptions' => true,
            'returns_refunds' => true,
            'reviews_disputes' => true,
            'loyalty_program' => true,
            'analytics' => true,
            'multi_language' => true,
        ];
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->getFeatures();

        return $features[$feature] ?? false;
    }

    public function getDomain(): string
    {
        if ($this->domain) {
            return $this->domain;
        }

        if ($this->subdomain) {
            return $this->subdomain.'.'.config('app.domain', 'glfbikube.local');
        }

        return config('app.url');
    }
}
