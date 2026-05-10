<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPointsTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'helper_profile_id',
        'delta_points',
        'reason_code',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'delta_points' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function helperProfile(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'helper_profile_id');
    }
}
