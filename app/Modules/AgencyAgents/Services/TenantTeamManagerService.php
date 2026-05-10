<?php

namespace App\Modules\AgencyAgents\Services;

use App\Models\Domain\AgentOS\Models\TenantAgentTeam;
use App\Modules\AgencyAgents\Models\Agent;
use Illuminate\Support\Collection;

class TenantTeamManagerService
{
    public function createTeam(array $data): TenantAgentTeam
    {
        return TenantAgentTeam::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'director_agent_id' => $data['director_agent_id'] ?? null,
            'active_agents' => $data['active_agents'] ?? [],
            'configuration' => $data['configuration'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function getTeamForTenant(?int $tenantId): ?TenantAgentTeam
    {
        return TenantAgentTeam::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function addAgentToTeam(int $teamId, int $agentId): bool
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team) {
            return false;
        }

        $activeAgents = $team->active_agents ?? [];
        if (!in_array($agentId, $activeAgents)) {
            $activeAgents[] = $agentId;
            $team->active_agents = $activeAgents;
            $team->save();
        }

        return true;
    }

    public function removeAgentFromTeam(int $teamId, int $agentId): bool
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team) {
            return false;
        }

        $activeAgents = $team->active_agents ?? [];
        $activeAgents = array_filter($activeAgents, fn($id) => $id !== $agentId);
        $team->active_agents = array_values($activeAgents);
        $team->save();

        return true;
    }

    public function setDirectorAgent(int $teamId, int $agentId): bool
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team) {
            return false;
        }

        $team->director_agent_id = $agentId;
        $team->save();

        return true;
    }

    public function getTeamAgents(int $teamId): Collection
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team || empty($team->active_agents)) {
            return collect();
        }

        return Agent::whereIn('id', $team->active_agents)->get();
    }

    public function getTeamStats(int $teamId): array
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team) {
            return [];
        }

        $agents = $this->getTeamAgents($teamId);
        $categoryCounts = $agents->groupBy('category')->map->count();

        return [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'total_agents' => $agents->count(),
            'director_agent' => $team->directorAgent?->name,
            'categories' => $categoryCounts->toArray(),
            'is_active' => $team->is_active,
        ];
    }

    public function createDefaultTeam(?int $tenantId = null): TenantAgentTeam
    {
        // Find or create director agent
        $director = Agent::where('name', 'like', '%director%')
            ->orWhere('name', 'like', '%orchestrator%')
            ->first();

        if (!$director) {
            $director = Agent::create([
                'name' => 'Director Agent',
                'slug' => 'director-agent',
                'category' => 'strategy',
                'description' => 'AI orchestrator for multi-agent workflows',
                'identity_memory' => 'You are the Director Agent responsible for coordinating teams of specialized agents.',
                'core_mission' => 'Analyze requests, assemble teams, and orchestrate multi-agent workflows.',
                'status' => 'active',
            ]);
        }

        // Get top agents from each category
        $categories = ['engineering', 'design', 'marketing', 'strategy', 'sales'];
        $activeAgents = [];

        foreach ($categories as $category) {
            $agents = Agent::where('category', $category)
                ->where('status', 'active')
                ->limit(10)
                ->pluck('id')
                ->toArray();
            $activeAgents = array_merge($activeAgents, $agents);
        }

        return $this->createTeam([
            'tenant_id' => $tenantId,
            'name' => 'Default Team',
            'director_agent_id' => $director->id,
            'active_agents' => $activeAgents,
            'configuration' => [
                'max_concurrent_agents' => 10,
                'default_model' => 'sonnet-4.5',
            ],
        ]);
    }

    public function updateTeamConfiguration(int $teamId, array $config): bool
    {
        $team = TenantAgentTeam::find($teamId);
        if (!$team) {
            return false;
        }

        $team->configuration = array_merge($team->configuration ?? [], $config);
        $team->save();

        return true;
    }
}
