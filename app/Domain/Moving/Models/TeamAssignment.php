<?php

namespace App\Domain\Moving\Models;

use Illuminate\Database\Eloquent\Model;

class TeamAssignment extends Model
{
    protected $table = 'team_assignments';

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'team_lead_executor_id',
        'member_executor_ids_json',
        'status',
        'eta_at',
        'metadata',
    ];

    protected $casts = [
        'member_executor_ids_json' => 'array',
        'eta_at' => 'datetime',
        'metadata' => 'array',
    ];
}
