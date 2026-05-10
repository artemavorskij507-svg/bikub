<?php

namespace App\Models\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAgentTeam extends Model
{
    protected $table = 'tenant_agent_teams';

    protected $fillable = [
        'tenant_id',
        'name',
        'director_agent_id',
        'active_agents',
        'configuration',
        'is_active',
    ];

    protected $casts = [
        'active_agents' => 'array',
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    public function directorAgent(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AgencyAgents\Models\Agent::class, 'director_agent_id');
    }

    public function getActiveAgentModels()
    {
        if (empty($this->active_agents)) {
            return collect();
        }

        return \App\Modules\AgencyAgents\Models\Agent::whereIn('id', $this->active_agents)->get();
    }
}
