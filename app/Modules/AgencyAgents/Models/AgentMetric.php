<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMetric extends Model
{
    use HasFactory;

    protected $table = 'agency_agent_metrics';

    protected $fillable = [
        'agent_id',
        'metric_type',
        'value',
        'unit',
        'recorded_at',
        'metadata',
        'context',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'value' => 'decimal:4',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public static function recordPerformance(Agent $agent, float $score, array $context = []): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'metric_type' => 'performance',
            'value' => $score,
            'unit' => 'score',
            'context' => $context,
            'recorded_at' => now(),
        ]);
    }

    public static function recordTaskCompletion(Agent $agent, int $taskId, float $duration): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'metric_type' => 'task_completion',
            'value' => $duration,
            'unit' => 'seconds',
            'context' => ['task_id' => $taskId],
            'recorded_at' => now(),
        ]);
    }

    public static function recordCommunication(Agent $sender, Agent $receiver, string $type): self
    {
        return self::create([
            'agent_id' => $sender->id,
            'metric_type' => 'communication',
            'value' => 1,
            'unit' => 'count',
            'context' => [
                'receiver_id' => $receiver->id,
                'communication_type' => $type,
            ],
            'recorded_at' => now(),
        ]);
    }
}
