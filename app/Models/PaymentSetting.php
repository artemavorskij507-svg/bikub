<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'label',
        'publishable_key',
        'secret_key',
        'webhook_secret',
        'currency',
        'is_active',
        'is_test_mode',
        'additional_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'additional_config' => 'array',
    ];

    /**
     * Get active Stripe settings.
     */
    public static function getStripeSettings()
    {
        return self::where('gateway', 'stripe')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get publishable key.
     */
    public function getPublishableKey(): string
    {
        return $this->publishable_key ?? env('STRIPE_PUBLISHABLE_KEY', '');
    }

    /**
     * Get secret key.
     */
    public function getSecretKey(): string
    {
        return $this->secret_key ?? env('STRIPE_SECRET_KEY', '');
    }

    /**
     * Check if settings are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->publishable_key) && ! empty($this->secret_key);
    }
}
