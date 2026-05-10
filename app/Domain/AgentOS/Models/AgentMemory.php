<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentMemory extends Model
{
    use HasFactory;

    protected $table = 'agent_memories';

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'run_id',
        'step_id',
        'agent_key',
        'scope',
        'memory_type',
        'role',
        'content',
        'summary',
        'importance',
        'tokens_estimate',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }

    public function step()
    {
        return $this->belongsTo(AgentStep::class, 'step_id');
    }
}

