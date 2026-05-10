<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentValidation extends Model
{
    use HasFactory;

    protected $table = 'agent_validations';

    protected $fillable = [
        'run_id',
        'step_id',
        'artifact_id',
        'validator_type',
        'result',
        'score',
        'notes',
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

    public function artifact()
    {
        return $this->belongsTo(AgentArtifact::class, 'artifact_id');
    }
}
