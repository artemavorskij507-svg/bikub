<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentModuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'module_key',
        'role',
        'access_level',
        'priority',
        'zones',
        'routing_preferences',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'zones' => 'array',
        'routing_preferences' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}

