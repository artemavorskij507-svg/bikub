<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'assignment_id',
        'actor_type',
        'actor_id',
        'event_type',
        'event_payload',
        'occurred_at',
    ];

    protected $casts = [
        'event_payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
