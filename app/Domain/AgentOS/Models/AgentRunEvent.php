<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRunEvent extends Model
{
    use HasFactory;

    protected $table = 'agent_run_events';

    protected $fillable = [
        'run_id',
        'step_id',
        'thread_id',
        'organization_id',
        'tenant_id',
        'event_type',
        'event_level',
        'actor_type',
        'actor_key',
        'message',
        'payload',
        'dedupe_key',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }

    public function step()
    {
        return $this->belongsTo(AgentStep::class, 'step_id');
    }

    public function thread()
    {
        return $this->belongsTo(AgentRunThread::class, 'thread_id');
    }
}
