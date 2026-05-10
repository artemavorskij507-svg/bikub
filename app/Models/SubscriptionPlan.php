<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'description',
        'period',
        'price',
        'features',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id')->where('status', 'active');
    }
}
