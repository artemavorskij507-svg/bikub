<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaTimer extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'assignment_id',
        'sla_policy_id',
        'metric_name',
        'target_at',
        'warning_at',
        'breach_at',
        'status',
        'dispatch_deadline_at',
        'arrival_deadline_at',
        'completion_deadline_at',
        'dispatch_state',
        'arrival_state',
        'completion_state',
        'last_evaluated_at',
    ];

    protected $casts = [
        'target_at' => 'datetime',
        'warning_at' => 'datetime',
        'breach_at' => 'datetime',
        'dispatch_deadline_at' => 'datetime',
        'arrival_deadline_at' => 'datetime',
        'completion_deadline_at' => 'datetime',
        'last_evaluated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'context' => 'array',
    ];

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }
}
