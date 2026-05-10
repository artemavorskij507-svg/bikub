<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentEventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'module_key',
        'event_name',
        'trigger',
        'source_agent_id',
        'access_level',
        'status',
        'payload',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function sourceAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'source_agent_id');
    }
}

