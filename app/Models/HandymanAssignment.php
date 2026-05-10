<?php

namespace App\Models;

use App\Models\Moving\ExecutorProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandymanAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'executor_profile_id',
        'repair_project_id',
        'status',
        'planned_start_at',
        'planned_finish_at',
        'actual_start_at',
        'actual_finish_at',
        'score',
        'is_primary',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'planned_start_at' => 'datetime',
        'planned_finish_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_finish_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function executorProfile(): BelongsTo
    {
        return $this->belongsTo(ExecutorProfile::class);
    }

    public function repairProject(): BelongsTo
    {
        return $this->belongsTo(RepairProject::class);
    }
}
