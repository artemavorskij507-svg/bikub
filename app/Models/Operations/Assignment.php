<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'executor_id',
        'dispatch_run_id',
        'assignment_mode',
        'status',
        'score',
        'score_breakdown',
        'eta_at',
        'acceptance_deadline_at',
        'acceptance_timeout_seconds',
        'arrival_deadline_at',
        'completion_deadline_at',
        'accepted_at',
        'arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'route_plan',
        'metadata',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'eta_at' => 'datetime',
        'acceptance_deadline_at' => 'datetime',
        'acceptance_timeout_seconds' => 'integer',
        'arrival_deadline_at' => 'datetime',
        'completion_deadline_at' => 'datetime',
        'accepted_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'route_plan' => 'array',
        'metadata' => 'array',
    ];

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class);
    }

    public function dispatchRun(): BelongsTo
    {
        return $this->belongsTo(DispatchRun::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(JobStateTransition::class);
    }
}
