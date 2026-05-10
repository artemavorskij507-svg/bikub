<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTwoFactorSetting extends Model
{
    protected $table = 'user_two_factor_settings';

    protected $fillable = [
        'user_id', 'secret', 'recovery_codes', 'enabled_at', 'confirmed_at',
    ];

    protected $casts = [
        'recovery_codes' => 'array',
        'enabled_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    // @gdpr-critical: stores encrypted secret and recovery codes
}
