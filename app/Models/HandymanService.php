<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanService extends Model
{
    use HasFactory;

    public const PRICING_HOURLY = 'HOURLY';

    public const PRICING_FIXED = 'FIXED';

    protected $fillable = [
        'code',
        'slug',
        'name',
        'description',
        'category',
        'pricing_mode',
        'base_rate_minor',
        'estimated_duration_minutes',
        'required_skills',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function isHourly(): bool
    {
        return $this->pricing_mode === self::PRICING_HOURLY;
    }

    public function isFixed(): bool
    {
        return $this->pricing_mode === self::PRICING_FIXED;
    }
}
