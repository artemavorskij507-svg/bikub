<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'branding',
        'features',
        'policies',
        'integrations',
    ];

    protected $casts = [
        'branding' => 'array',
        'features' => 'array',
        'policies' => 'array',
        'integrations' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getBranding(): array
    {
        return $this->branding ?? [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'accent_color' => '#F59E0B',
            'logo_url' => null,
            'font_family' => 'Inter',
            'custom_css' => null,
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
            'search_facets' => true,
            'geo_routing' => true,
        ];
    }

    public function getPolicies(): array
    {
        return $this->policies ?? [
            'return_policy' => 'Standard 14-day return policy',
            'privacy_policy' => 'We respect your privacy',
            'terms_of_service' => 'Standard terms apply',
            'shipping_policy' => 'Free shipping on orders over 500 NOK',
            'refund_policy' => 'Full refund within return window',
        ];
    }

    public function getIntegrations(): array
    {
        return $this->integrations ?? [
            'stripe' => [
                'enabled' => true,
                'test_mode' => true,
            ],
            'vipps' => [
                'enabled' => false,
                'test_mode' => true,
            ],
            'email_service' => [
                'provider' => 'mailgun',
                'enabled' => true,
            ],
            'analytics' => [
                'google_analytics' => null,
                'facebook_pixel' => null,
            ],
        ];
    }
}
