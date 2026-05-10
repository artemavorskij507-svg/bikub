<?php

namespace App\Modules\AgencyAgents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentActivity extends Model
{
    use HasFactory;

    protected $table = 'agency_agent_activities';

    protected $fillable = [
        'agent_id',
        'activity_type',
        'zone',
        'description',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isOngoing(): bool
    {
        return $this->ended_at === null;
    }

    public function getDuration(): int
    {
        if ($this->ended_at) {
            return $this->started_at->diffInSeconds($this->ended_at);
        }
        
        return $this->started_at->diffInSeconds(now());
    }

    public function end(): void
    {
        $this->update(['ended_at' => now()]);
    }

    public static function startActivity(Agent $agent, string $type, string $zone, string $description = null): self
    {
        // End any ongoing activity
        self::where('agent_id', $agent->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => $type,
            'zone' => $zone,
            'description' => $description,
            'started_at' => now(),
        ]);
    }

    public static function logMovement(Agent $agent, string $fromZone, string $toZone): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => 'movement',
            'zone' => $toZone,
            'description' => "Перемещение из {$fromZone} в {$toZone}",
            'started_at' => now(),
            'ended_at' => now(),
            'metadata' => [
                'from_zone' => $fromZone,
                'to_zone' => $toZone,
            ],
        ]);
    }

    public static function logTaskStart(Agent $agent, AgentTask $task): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => 'task_started',
            'zone' => $agent->current_zone,
            'description' => "Начата задача: {$task->title}",
            'started_at' => now(),
            'metadata' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
            ],
        ]);
    }

    public static function logTaskComplete(Agent $agent, AgentTask $task): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => 'task_completed',
            'zone' => $agent->current_zone,
            'description' => "Завершена задача: {$task->title}",
            'started_at' => now(),
            'ended_at' => now(),
            'metadata' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
            ],
        ]);
    }

    public static function logCommunication(Agent $sender, Agent $receiver, string $messageType): self
    {
        return self::create([
            'agent_id' => $sender->id,
            'activity_type' => 'communication',
            'zone' => $sender->current_zone,
            'description' => "Отправлено сообщение {$receiver->name}",
            'started_at' => now(),
            'ended_at' => now(),
            'metadata' => [
                'receiver_id' => $receiver->id,
                'receiver_name' => $receiver->name,
                'message_type' => $messageType,
            ],
        ]);
    }

    public static function logBreakStart(Agent $agent, string $breakType): self
    {
        $zone = match($breakType) {
            'lunch' => 'cafeteria',
            'coffee' => 'lounge',
            default => 'break_room',
        };

        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => 'break_started',
            'zone' => $zone,
            'description' => "Начат перерыв: {$breakType}",
            'started_at' => now(),
            'metadata' => [
                'break_type' => $breakType,
            ],
        ]);
    }

    public static function logBreakEnd(Agent $agent): self
    {
        return self::create([
            'agent_id' => $agent->id,
            'activity_type' => 'break_ended',
            'zone' => $agent->current_zone,
            'description' => 'Завершен перерыв',
            'started_at' => now(),
            'ended_at' => now(),
        ]);
    }

    public static function getRecentActivities(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('agent')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function getAgentActivities(Agent $agent, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('agent_id', $agent->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->latest()
            ->get();
    }

    public static function getZoneActivities(string $zone, int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('zone', $zone)
            ->where('started_at', '>=', now()->subHours($hours))
            ->latest()
            ->get();
    }

    public static function getActivityStats(int $hours = 24): array
    {
        $activities = self::where('started_at', '>=', now()->subHours($hours))->get();

        return [
            'total' => $activities->count(),
            'by_type' => $activities->groupBy('activity_type')->map->count(),
            'by_zone' => $activities->groupBy('zone')->map->count(),
            'movements' => $activities->where('activity_type', 'movement')->count(),
            'tasks_started' => $activities->where('activity_type', 'task_started')->count(),
            'tasks_completed' => $activities->where('activity_type', 'task_completed')->count(),
            'communications' => $activities->where('activity_type', 'communication')->count(),
        ];
    }
}
