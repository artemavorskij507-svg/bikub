<?php

namespace App\Models\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentSkill extends Model
{
    protected $table = 'agent_skills';

    protected $fillable = [
        'name',
        'category',
        'description',
        'prompt_template',
        'tools',
        'is_active',
    ];

    protected $casts = [
        'tools' => 'array',
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(AgentSkillAssignment::class, 'skill_id');
    }

    public function agents()
    {
        return $this->belongsToMany(
            \App\Modules\AgencyAgents\Models\Agent::class,
            'agent_skill_assignments',
            'skill_id',
            'agent_id'
        )->withPivot('proficiency_level')->withTimestamps();
    }
}
