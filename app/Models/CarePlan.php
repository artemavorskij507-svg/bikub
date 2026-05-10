<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_profile_id',
        'trusted_contact_id',
        'care_service_id',
        'service_type_code',
        'frequency',
        'day_of_week',
        'time_of_day',
        'duration_minutes',
        'preferred_helper_level',
        'preferred_helper_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function trustedContact(): BelongsTo
    {
        return $this->belongsTo(TrustedContact::class, 'trusted_contact_id');
    }

    public function careService(): BelongsTo
    {
        return $this->belongsTo(CareService::class, 'care_service_id');
    }

    public function preferredHelper(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'preferred_helper_id');
    }

    public function careOrders(): HasMany
    {
        return $this->hasMany(CareOrderDetails::class, 'care_plan_id');
    }
}
