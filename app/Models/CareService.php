<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareService extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'required_level',
        'base_duration_minutes',
        'base_price_nok',
        'is_recurring_available',
        'is_active',
    ];

    protected $casts = [
        'base_price_nok' => 'decimal:2',
        'is_recurring_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function careOrders(): HasMany
    {
        return $this->hasMany(CareOrderDetails::class, 'care_service_id');
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class, 'care_service_id');
    }
}
