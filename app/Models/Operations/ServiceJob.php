<?php

namespace App\Models\Operations;

use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\SlaPolicy;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'source_type',
        'source_id',
        'order_id',
        'task_id',
        'service_domain',
        'job_type',
        'job_kind',
        'status',
        'priority',
        'customer_id',
        'executor_id',
        'assignment_id',
        'pickup_point',
        'dropoff_point',
        'service_point',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'service_lat',
        'service_lng',
        'time_window_start',
        'time_window_end',
        'service_duration_minutes',
        'required_team_size',
        'required_skills',
        'required_capacity',
        'required_equipment',
        'zone_id',
        'geo_zone_id',
        'schedule_slot_id',
        'sla_policy_id',
        'customer_eta_at',
        'promised_sla_minutes',
        'promised_eta_at',
        'promised_completion_at',
        'price_snapshot',
        'created_by',
        'updated_by',
        'actual_started_at',
        'actual_completed_at',
        'metadata',
    ];

    protected $casts = [
        'pickup_point' => 'array',
        'dropoff_point' => 'array',
        'service_point' => 'array',
        'price_snapshot' => 'array',
        'required_skills' => 'array',
        'required_capacity' => 'array',
        'required_equipment' => 'array',
        'metadata' => 'array',
        'required_team_size' => 'integer',
        'time_window_start' => 'datetime',
        'time_window_end' => 'datetime',
        'customer_eta_at' => 'datetime',
        'promised_eta_at' => 'datetime',
        'promised_completion_at' => 'datetime',
        'actual_started_at' => 'datetime',
        'actual_completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class, 'zone_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(Assignment::class)->whereIn('status', ['proposed', 'offered', 'accepted', 'active', 'reassigned']);
    }

    public function currentAssignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class, 'executor_id');
    }

    public function stateTransitions(): HasMany
    {
        return $this->hasMany(JobStateTransition::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(JobTimeline::class)->orderBy('occurred_at');
    }

    public function timelines(): HasMany
    {
        return $this->timeline();
    }

    public function slaTimer()
    {
        return $this->hasOne(SlaTimer::class);
    }

    public function slaTimers(): HasMany
    {
        return $this->hasMany(SlaTimer::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(OperationException::class);
    }
}
