<?php

namespace App\Models\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentModelConfig extends Model
{
    protected $table = 'agent_model_configs';

    protected $fillable = [
        'agent_id',
        'model_name',
        'temperature',
        'max_tokens',
        'system_prompt_override',
        'additional_config',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'additional_config' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AgencyAgents\Models\Agent::class, 'agent_id');
    }
}
