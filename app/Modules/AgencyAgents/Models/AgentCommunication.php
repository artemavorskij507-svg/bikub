<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommunication extends Model
{
    use HasFactory;

    protected $table = 'agency_agent_communications';

    protected $fillable = [
        'sender_agent_id',
        'receiver_agent_id',
        'message_type',
        'content',
        'status',
        'read_at',
        'metadata',
        'priority',
        'related_task_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'sent',
        'priority' => 'normal',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sender_agent_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'receiver_agent_id');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'related_task_id');
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }
}
