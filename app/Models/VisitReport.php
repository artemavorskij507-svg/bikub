<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'care_order_details_id',
        'helper_profile_id',
        'started_at',
        'ended_at',
        'status',
        'summary',
        'client_mood',
        'issues_noted',
        'followup_recommended',
        'followup_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'followup_recommended' => 'boolean',
    ];

    public function careOrderDetails(): BelongsTo
    {
        return $this->belongsTo(CareOrderDetails::class, 'care_order_details_id');
    }

    public function helperProfile(): BelongsTo
    {
        return $this->belongsTo(SocialHelperProfile::class, 'helper_profile_id');
    }
}
