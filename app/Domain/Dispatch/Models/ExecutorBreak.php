<?php

namespace App\Domain\Dispatch\Models;

use App\Models\Operations\Executor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorBreak extends Model
{
    protected $table = 'executor_breaks';

    protected $fillable = [
        'organization_id','tenant_id','executor_id','shift_date','break_start_at','break_end_at','type','is_paid',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class, 'executor_id');
    }
}
