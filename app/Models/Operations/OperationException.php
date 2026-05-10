<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationException extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'service_job_id',
        'assignment_id',
        'executor_id',
        'type',
        'exception_type',
        'severity',
        'status',
        'detected_by',
        'owner_user_id',
        'owner_id',
        'detected_at',
        'acknowledged_at',
        'resolved_at',
        'root_cause',
        'resolution_code',
        'resolution_notes',
        'payload',
        'summary',
        'remediation',
        'metadata',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'payload' => 'array',
        'remediation' => 'array',
        'metadata' => 'array',
    ];

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function getCanonicalTypeAttribute(): string
    {
        return (string) ($this->type ?: $this->exception_type ?: 'unknown');
    }
}
