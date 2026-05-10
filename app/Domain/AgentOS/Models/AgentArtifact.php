<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentArtifact extends Model
{
    use HasFactory;

    protected $table = 'agent_artifacts';

    protected $fillable = [
        'run_id',
        'step_id',
        'organization_id',
        'tenant_id',
        'artifact_type',
        'path',
        'content',
        'validation_status',
        'metadata',
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
