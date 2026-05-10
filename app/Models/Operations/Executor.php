<?php

namespace App\Models\Operations;

use App\Models\Employee;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Executor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'employee_id',
        'user_id',
        'partner_id',
        'code',
        'name',
        'display_name',
        'phone',
        'executor_type',
        'status',
        'availability_mode',
        'home_zone_id',
        'current_zone_id',
        'vehicle_type',
        'skills',
        'capabilities',
        'is_dispatchable',
        'max_concurrent_jobs',
        'capacity',
        'equipment',
        'metadata',
        'last_seen_at',
    ];

    protected $casts = [
        'skills' => 'array',
        'capabilities' => 'array',
        'capacity' => 'array',
        'equipment' => 'array',
        'metadata' => 'array',
        'is_dispatchable' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ExecutorSkill::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ExecutorShift::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ExecutorLocation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
