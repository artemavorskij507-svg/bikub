<?php

namespace App\Modules\AgencyAgents\Services;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentEventLog;
use App\Modules\AgencyAgents\Models\AgentTask;

class AgentEventBusService
{
    public function __construct(private readonly AgentCommunicationService $communications)
    {
    }

    public function publish(string $moduleKey, string $eventName, array $payload = [], ?Agent $sourceAgent = null, ?string $trigger = null): array
    {
        return $this->communications->emitModuleEvent($moduleKey, $eventName, $payload, $sourceAgent, $trigger);
    }

    public function createReactionTask(Agent $agent, string $title, string $description, array $metadata = []): AgentTask
    {
        return $agent->tasks()->create([
            'title' => $title,
            'description' => $description,
            'status' => 'pending',
            'priority' => $metadata['priority'] ?? 'high',
            'category' => $metadata['category'] ?? 'logistics',
            'target_zone' => $metadata['target_zone'] ?? $agent->current_zone,
            'metadata' => $metadata,
        ]);
    }

    public function markProcessed(AgentEventLog $eventLog, array $metadata = []): void
    {
        $eventLog->update([
            'status' => 'processed',
            'processed_at' => now(),
            'metadata' => array_merge($eventLog->metadata ?? [], $metadata),
        ]);
    }
}

