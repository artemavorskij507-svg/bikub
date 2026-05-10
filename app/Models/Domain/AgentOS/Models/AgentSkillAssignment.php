<?php

namespace App\Models\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSkillAssignment extends Model
{
    protected $table = 'agent_skill_assignments';

    protected $fillable = [
        'agent_id',
        'skill_id',
        'proficiency_level',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AgencyAgents\Models\Agent::class, 'agent_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(AgentSkill::class, 'skill_id');
    }
}
