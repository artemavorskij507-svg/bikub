<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPointsBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'helper_profile_id',
        'balance_points',
        'lifetime_points',
    ];

    protected $casts = [
        'balance_points' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function helperProfile(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'helper_profile_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CommunityPointsTransaction::class, 'helper_profile_id', 'helper_profile_id');
    }
}
