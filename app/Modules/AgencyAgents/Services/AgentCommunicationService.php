<?php

namespace App\Modules\AgencyAgents\Services;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentCommunication;
use App\Modules\AgencyAgents\Models\AgentEventLog;
use App\Modules\AgencyAgents\Models\AgentModuleAssignment;
use App\Modules\AgencyAgents\Models\AgentTask;
use Illuminate\Support\Facades\Log;

class AgentCommunicationService
{
    public function sendMessage(
        Agent $sender,
        Agent $receiver,
        string $content,
        string $type = 'message',
        string $priority = 'normal',
        ?int $relatedTaskId = null
    ): AgentCommunication {
        $communication = AgentCommunication::create([
            'sender_agent_id' => $sender->id,
            'receiver_agent_id' => $receiver->id,
            'message_type' => $type,
            'content' => $content,
            'priority' => $priority,
            'related_task_id' => $relatedTaskId,
            'metadata' => [
                'sent_at' => now()->toISOString(),
                'sender_name' => $sender->name,
                'receiver_name' => $receiver->name,
                'sender_zone' => $sender->current_zone,
                'receiver_zone' => $receiver->current_zone,
            ],
        ]);

        AgentActivity::logCommunication($sender, $receiver, $type);

        Log::info('Agent message sent', [
            'sender' => $sender->name,
            'receiver' => $receiver->name,
            'type' => $type,
            'sender_zone' => $sender->current_zone,
            'receiver_zone' => $receiver->current_zone,
        ]);

        return $communication;
    }

    public function emitModuleEvent(
        string $moduleKey,
        string $eventName,
        array $payload = [],
        ?Agent $sender = null,
        ?string $trigger = null
    ): array {
        $assignments = AgentModuleAssignment::query()
            ->with('agent')
            ->where('module_key', $moduleKey)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        $logs = [];

        foreach ($assignments as $assignment) {
            $logs[] = AgentEventLog::create([
                'agent_id' => $assignment->agent_id,
                'module_key' => $moduleKey,
                'event_name' => $eventName,
                'trigger' => $trigger,
                'source_agent_id' => $sender?->id,
                'access_level' => $assignment->access_level,
                'status' => 'received',
                'payload' => $payload,
                'metadata' => [
                    'assignment_role' => $assignment->role,
                ],
            ]);
        }

        Log::info('Agent module event emitted', [
            'module' => $moduleKey,
            'event' => $eventName,
            'recipients' => count($logs),
        ]);

        return $logs;
    }

    public function broadcastMessage(Agent $sender, string $content, string $type = 'broadcast'): array
    {
        $receivers = Agent::where('id', '!=', $sender->id)
            ->where('status', '!=', 'offline')
            ->get();

        $communications = [];

        foreach ($receivers as $receiver) {
            $communications[] = $this->sendMessage($sender, $receiver, $content, $type);
        }

        return $communications;
    }

    public function requestAssistance(Agent $requester, Agent $helper, string $taskDescription, ?int $taskId = null): AgentCommunication
    {
        return $this->sendMessage($requester, $helper, "Requesting assistance: {$taskDescription}", 'assistance_request', 'high', $taskId);
    }

    public function shareKnowledge(Agent $sharer, Agent $receiver, string $knowledge): AgentCommunication
    {
        return $this->sendMessage($sharer, $receiver, "Knowledge share: {$knowledge}", 'knowledge_share', 'normal');
    }

    public function assignTask(Agent $assigner, Agent $assignee, AgentTask $task): AgentCommunication
    {
        return $this->sendMessage($assigner, $assignee, "Task assigned: {$task->title}", 'task_assignment', 'high', $task->id);
    }

    public function getUnreadMessages(Agent $agent): \Illuminate\Database\Eloquent\Collection
    {
        return AgentCommunication::where('receiver_agent_id', $agent->id)
            ->where('status', 'sent')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getConversationHistory(Agent $agent1, Agent $agent2, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AgentCommunication::where(function ($query) use ($agent1, $agent2) {
            $query->where('sender_agent_id', $agent1->id)->where('receiver_agent_id', $agent2->id);
        })->orWhere(function ($query) use ($agent1, $agent2) {
            $query->where('sender_agent_id', $agent2->id)->where('receiver_agent_id', $agent1->id);
        })->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    public function markAllAsRead(Agent $agent): int
    {
        return AgentCommunication::where('receiver_agent_id', $agent->id)
            ->where('status', 'sent')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }

    public function getCommunicationStats(Agent $agent): array
    {
        $sent = AgentCommunication::where('sender_agent_id', $agent->id)->count();
        $received = AgentCommunication::where('receiver_agent_id', $agent->id)->count();
        $unread = AgentCommunication::where('receiver_agent_id', $agent->id)->where('status', 'sent')->count();

        return [
            'sent' => $sent,
            'received' => $received,
            'unread' => $unread,
            'total' => $sent + $received,
        ];
    }

    public function findBestAgentForTask(string $category, array $requiredSkills = []): ?Agent
    {
        $query = Agent::where('category', $category)->where('status', '!=', 'offline');

        if (!empty($requiredSkills)) {
            $query->where(function ($q) use ($requiredSkills) {
                foreach ($requiredSkills as $skill) {
                    $q->orWhereJsonContains('metadata->skills', $skill);
                }
            });
        }

        return $query->orderBy('performance_score', 'desc')->first();
    }

    public function coordinateCollaboration(array $agents, string $taskDescription): array
    {
        $communications = [];
        $leadAgent = $agents[0];

        foreach ($agents as $agent) {
            if ($agent->id !== $leadAgent->id) {
                $communications[] = $this->sendMessage($leadAgent, $agent, "Collaboration initiated: {$taskDescription}", 'collaboration', 'high');
            }
        }

        return $communications;
    }

    public function notifyZoneChange(Agent $agent, string $fromZone, string $toZone): void
    {
        $agentsInZone = Agent::where('current_zone', $toZone)
            ->where('id', '!=', $agent->id)
            ->where('status', '!=', 'offline')
            ->get();

        foreach ($agentsInZone as $otherAgent) {
            $this->sendMessage($agent, $otherAgent, "Moving to {$toZone}", 'zone_change', 'low');
        }
    }

    public function requestMeeting(Agent $organizer, array $participants, string $topic, string $zone = 'meeting_room'): array
    {
        $communications = [];

        foreach ($participants as $participant) {
            if ($participant->id !== $organizer->id) {
                $communications[] = $this->sendMessage($organizer, $participant, "Meeting request: {$topic} in {$zone}", 'meeting_request', 'high');
            }
        }

        return $communications;
    }

    public function getAgentsInZone(string $zone): \Illuminate\Database\Eloquent\Collection
    {
        return Agent::where('current_zone', $zone)->where('status', '!=', 'offline')->get();
    }

    public function getNearbyAgents(Agent $agent, float $radius = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Agent::where('id', '!=', $agent->id)
            ->where('status', '!=', 'offline')
            ->where('current_zone', $agent->current_zone)
            ->get()
            ->filter(function ($otherAgent) use ($agent, $radius) {
                $distance = sqrt(
                    pow($agent->position_x - $otherAgent->position_x, 2) +
                    pow($agent->position_y - $otherAgent->position_y, 2)
                );

                return $distance <= $radius;
            });
    }
}

