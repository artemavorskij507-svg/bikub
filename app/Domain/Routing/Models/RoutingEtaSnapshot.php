<?php

namespace App\Domain\Routing\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingEtaSnapshot extends Model
{
    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'executor_id',
        'dispatch_run_id',
        'dispatch_candidate_id',
        'heuristic_provider',
        'heuristic_eta_seconds',
        'heuristic_distance_meters',
        'routing_provider',
        'routing_eta_seconds',
        'routing_distance_meters',
        'eta_delta_seconds',
        'distance_delta_meters',
        'would_change_ranking',
        'context',
    ];

    protected $casts = [
        'would_change_ranking' => 'boolean',
        'context' => 'array',
    ];
}

