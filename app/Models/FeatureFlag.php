<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'enabled',
        'is_active',
        'default_on',
        'rollout_percent',
        'settings',
        'rules',
        'starts_at',
        'ends_at',
        'enabled_by',
        'enabled_at',
        'reason',
        'owner_user_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_active' => 'boolean',
        'default_on' => 'boolean',
        'settings' => 'array',
        'rules' => 'array',
        'rollout_percent' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];

    /**
     * Get the user who enabled this flag.
     */
    public function enabledBy()
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }

    /**
     * Get the scopes for this feature flag.
     */
    public function scopes()
    {
        return $this->hasMany(FeatureFlagScope::class, 'flag_id');
    }

    /**
     * Check if a feature flag is enabled.
     */
    public static function isEnabled(string $key): bool
    {
        $flag = static::where('key', $key)->where('is_active', true)->first();

        return $flag ? ($flag->enabled ?? $flag->default_on ?? false) : config("feature_flags.{$key}", false);
    }

    /**
     * Get feature flag settings.
     */
    public static function getSettings(string $key): array
    {
        $flag = static::where('key', $key)->where('is_active', true)->first();
        if ($flag && ($flag->enabled ?? $flag->default_on ?? false)) {
            return $flag->settings ?? [];
        }

        // Fallback to config
        return config("feature_flags.{$key}", []) ?? [];
    }
}
