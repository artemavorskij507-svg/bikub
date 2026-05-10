<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatch_run_id',
        'service_job_id',
        'executor_id',
        'eligible',
        'score',
        'score_breakdown',
        'ineligibility_reasons',
    ];

    protected $casts = [
        'eligible' => 'boolean',
        'score_breakdown' => 'array',
        'ineligibility_reasons' => 'array',
    ];

    public function dispatchRun(): BelongsTo
    {
        return $this->belongsTo(DispatchRun::class);
    }

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class);
    }
}

