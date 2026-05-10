<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentStep extends Model
{
    use HasFactory;

    protected $table = 'agent_steps';

    protected $fillable = [
        'run_id',
        'parent_step_id',
        'organization_id',
        'tenant_id',
        'step_type',
        'name',
        'status',
        'is_risky',
        'depends_on',
        'input_payload',
        'output_payload',
        'artifact_contract',
        'validation_notes',
        'retry_count',
        'max_retries',
        'started_at',
        'heartbeat_at',
        'timeout_at',
        'finished_at',
        'reduced_confidence',
        'confidence_reason',
        'validation_result',
        'metadata',
    ];

    protected $casts = [
        'is_risky' => 'boolean',
        'depends_on' => 'array',
        'input_payload' => 'array',
        'output_payload' => 'array',
        'artifact_contract' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'timeout_at' => 'datetime',
        'finished_at' => 'datetime',
        'reduced_confidence' => 'boolean',
    ];

    public function run()
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_step_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_step_id');
    }

    public function artifacts()
    {
        return $this->hasMany(AgentArtifact::class, 'step_id');
    }

    public function validations()
    {
        return $this->hasMany(AgentValidation::class, 'step_id');
    }

    public function memories()
    {
        return $this->hasMany(AgentMemory::class, 'step_id');
    }
}
