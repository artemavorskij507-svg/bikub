<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadsidePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'service_type',
        'base_price',
        'requires_partner',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'requires_partner' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }
}
