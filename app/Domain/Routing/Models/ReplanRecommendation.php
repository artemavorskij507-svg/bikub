<?php

namespace App\Domain\Routing\Models;

use Illuminate\Database\Eloquent\Model;

class ReplanRecommendation extends Model
{
    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'current_executor_id',
        'recommended_executor_id',
        'type',
        'severity',
        'status',
        'payload',
        'detected_at',
        'acknowledged_at',
        'dismissed_at',
        'applied_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];
}

