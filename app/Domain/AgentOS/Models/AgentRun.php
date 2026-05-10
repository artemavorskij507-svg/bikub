<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRun extends Model
{
    use HasFactory;

    protected $table = 'agent_runs';

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'status',
        'risk_level',
        'requires_approval',
        'deployment_allowed',
        'idempotency_key',
        'goal',
        'terminal_reason',
        'metadata',
        'started_at',
        'finished_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'deployment_allowed' => 'boolean',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(AgentStep::class, 'run_id');
    }

    public function artifacts()
    {
        return $this->hasMany(AgentArtifact::class, 'run_id');
    }

    public function validations()
    {
        return $this->hasMany(AgentValidation::class, 'run_id');
    }

    public function memories()
    {
        return $this->hasMany(AgentMemory::class, 'run_id');
    }

    public function threads()
    {
        return $this->hasMany(AgentRunThread::class, 'run_id');
    }

    public function events()
    {
        return $this->hasMany(AgentRunEvent::class, 'run_id');
    }
}
