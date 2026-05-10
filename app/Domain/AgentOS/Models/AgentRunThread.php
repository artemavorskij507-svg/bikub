<?php

namespace App\Domain\AgentOS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRunThread extends Model
{
    use HasFactory;

    protected $table = 'agent_run_threads';

    protected $fillable = [
        'run_id',
        'organization_id',
        'tenant_id',
        'thread_key',
        'title',
        'is_system',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }

    public function events()
    {
        return $this->hasMany(AgentRunEvent::class, 'thread_id');
    }
}
