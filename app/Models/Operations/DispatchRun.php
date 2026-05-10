<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'service_job_id',
        'mode',
        'status',
        'filters',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(DispatchCandidate::class);
    }
}

