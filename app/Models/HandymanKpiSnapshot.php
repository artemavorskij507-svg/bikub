<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandymanKpiSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'executor_profile_id',
        'calculated_at',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'claims_count',
        'serious_claims_count',
        'avg_rating',
        'ratings_count',
        'on_time_rate',
        'avg_duration_minutes',
        'repeat_clients_count',
        'unique_clients_count',
        'quality_score',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'avg_rating' => 'float',
        'on_time_rate' => 'float',
    ];

    public function executor(): BelongsTo
    {
        return $this->belongsTo(ExecutorProfile::class, 'executor_profile_id');
    }
}
