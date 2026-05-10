<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialHelperProfile extends Model
{
    use HasFactory;

    protected $attributes = [
        'rating_avg' => 0,
        'rating_count' => 0,
    ];

    protected $fillable = [
        'user_id',
        'level',
        'organization_id',
        'display_name',
        'bio',
        'skills',
        'has_police_certificate',
        'police_certificate_verified_at',
        'first_aid_trained_at',
        'rating_avg',
        'rating_count',
        'is_active',
        'available_from',
        'available_to',
    ];

    protected $casts = [
        'skills' => 'array',
        'has_police_certificate' => 'boolean',
        'police_certificate_verified_at' => 'datetime',
        'first_aid_trained_at' => 'datetime',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'is_active' => 'boolean',
        'available_from' => 'datetime:H:i:sP',
        'available_to' => 'datetime:H:i:sP',
    ];

    protected static function booted(): void
    {
        static::creating(function (SocialHelperProfile $profile) {
            $profile->rating_avg ??= 0;
            $profile->rating_count ??= 0;
        });

        static::updating(function (SocialHelperProfile $profile) {
            $profile->rating_avg ??= 0;
            $profile->rating_count ??= 0;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public function careOrders(): HasMany
    {
        return $this->hasMany(CareOrderDetails::class, 'assigned_helper_id');
    }

    public function visitReports(): HasMany
    {
        return $this->hasMany(VisitReport::class, 'helper_profile_id');
    }

    public function communityPointsBalance(): HasOne
    {
        return $this->hasOne(CommunityPointsBalance::class, 'helper_profile_id');
    }

    public function communityPointsTransactions(): HasMany
    {
        return $this->hasMany(CommunityPointsTransaction::class, 'helper_profile_id');
    }
}
