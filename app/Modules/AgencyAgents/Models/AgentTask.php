<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTask extends Model
{
    use HasFactory;

    protected $table = 'agency_agent_tasks';

    protected $fillable = [
        'agent_id',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'assigned_by',
        'started_at',
        'completed_at',
        'deadline',
        'progress',
        'result',
        'error_message',
        'metadata',
        'dependencies',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dependencies' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'deadline' => 'datetime',
        'progress' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'medium',
        'progress' => 0,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(string $result = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100,
            'result' => $result,
        ]);
    }

    public function fail(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
    }
}
