<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSettings extends Model
{
    use HasFactory;

    protected $table = 'partner_settings';

    protected $fillable = [
        'partner_id',
        'notification_email',
        'sms_notifications_enabled',
        'email_notifications_enabled',
        'auto_assign_orders',
        'max_concurrent_orders',
        'order_timeout_minutes',
        'estimated_delivery_accuracy_km',
        'cancellation_allowed_minutes',
        'rating_minimum_threshold',
        'emergency_surcharge_percent',
        'operating_hours_start',
        'operating_hours_end',
        'timezone',
        'language',
        'api_key',
        'webhook_url',
        'features_enabled',
        'metadata',
    ];

    protected $casts = [
        'sms_notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'auto_assign_orders' => 'boolean',
        'max_concurrent_orders' => 'integer',
        'order_timeout_minutes' => 'integer',
        'estimated_delivery_accuracy_km' => 'decimal:2',
        'cancellation_allowed_minutes' => 'integer',
        'rating_minimum_threshold' => 'decimal:2',
        'emergency_surcharge_percent' => 'decimal:2',
        'features_enabled' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the partner that owns this settings record
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return in_array($feature, $this->features_enabled ?? []);
    }

    /**
     * Enable feature
     */
    public function enableFeature(string $feature): self
    {
        $features = $this->features_enabled ?? [];
        if (! in_array($feature, $features)) {
            $features[] = $feature;
            $this->update(['features_enabled' => $features]);
        }

        return $this;
    }

    /**
     * Disable feature
     */
    public function disableFeature(string $feature): self
    {
        $features = $this->features_enabled ?? [];
        $this->update(['features_enabled' => array_filter($features, fn ($f) => $f !== $feature)]);

        return $this;
    }
}
