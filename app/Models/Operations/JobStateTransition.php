<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobStateTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_job_id',
        'assignment_id',
        'from_status',
        'to_status',
        'event_type',
        'actor_id',
        'actor_type',
        'note',
        'payload',
        'transitioned_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'transitioned_at' => 'datetime',
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

