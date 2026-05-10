<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'phone',
        'email',
        'address_line',
        'postal_code',
        'city',
        'mobility_notes',
        'health_notes',
        'communication_preferences',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'communication_preferences' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trustedContacts(): HasMany
    {
        return $this->hasMany(TrustedContact::class, 'client_profile_id');
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class, 'client_profile_id');
    }

    public function careOrders(): HasMany
    {
        return $this->hasMany(CareOrderDetails::class, 'client_profile_id');
    }
}
