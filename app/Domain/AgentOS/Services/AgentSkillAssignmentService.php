<?php

namespace App\Domain\AgentOS\Services;

use App\Models\Domain\AgentOS\Models\AgentSkill;
use App\Models\Domain\AgentOS\Models\AgentSkillAssignment;
use App\Modules\AgencyAgents\Models\Agent;
use Illuminate\Support\Collection;

class AgentSkillAssignmentService
{
    public function assignSkillToAgent(int $agentId, int $skillId, int $proficiencyLevel = 3): AgentSkillAssignment
    {
        return AgentSkillAssignment::updateOrCreate(
            [
                'agent_id' => $agentId,
                'skill_id' => $skillId,
            ],
            [
                'proficiency_level' => $proficiencyLevel,
            ]
        );
    }

    public function assignSkillsByCategory(int $agentId, string $category, int $proficiencyLevel = 3): int
    {
        $agent = Agent::find($agentId);
        if (!$agent) {
            return 0;
        }

        $skills = AgentSkill::where('category', $category)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($skills as $skill) {
            $this->assignSkillToAgent($agentId, $skill->id, $proficiencyLevel);
            $count++;
        }

        return $count;
    }

    public function getAgentSkills(int $agentId): Collection
    {
        return AgentSkillAssignment::where('agent_id', $agentId)
            ->with('skill')
            ->get()
            ->map(function ($assignment) {
                return [
                    'skill' => $assignment->skill,
                    'proficiency_level' => $assignment->proficiency_level,
                ];
            });
    }

    public function generateAgentPromptWithSkills(int $agentId): string
    {
        $agent = Agent::find($agentId);
        if (!$agent) {
            return '';
        }

        $skills = $this->getAgentSkills($agentId);
        
        $prompt = "# Agent: {$agent->name}\n\n";
        $prompt .= "## Identity\n{$agent->identity_memory}\n\n";
        $prompt .= "## Core Mission\n{$agent->core_mission}\n\n";
        
        if ($skills->isNotEmpty()) {
            $prompt .= "## Skills\n";
            foreach ($skills as $skillData) {
                $skill = $skillData['skill'];
                $level = $skillData['proficiency_level'];
                $prompt .= "- {$skill->name} (Level {$level}/5)";
                if ($skill->description) {
                    $prompt .= ": {$skill->description}";
                }
                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        if ($agent->critical_rules) {
            $prompt .= "## Critical Rules\n{$agent->critical_rules}\n\n";
        }

        if ($agent->workflow_process) {
            $prompt .= "## Workflow Process\n{$agent->workflow_process}\n\n";
        }

        return $prompt;
    }

    public function bulkAssignSkillsByAgentCategory(): array
    {
        $stats = [];
        
        // Get all agents grouped by category
        $agents = Agent::all()->groupBy('category');
        
        foreach ($agents as $category => $categoryAgents) {
            $count = 0;
            foreach ($categoryAgents as $agent) {
                $assigned = $this->assignSkillsByCategory($agent->id, $category, 4);
                $count += $assigned;
            }
            $stats[$category] = [
                'agents' => $categoryAgents->count(),
                'skills_assigned' => $count,
            ];
        }

        return $stats;
    }

    public function removeSkillFromAgent(int $agentId, int $skillId): bool
    {
        return AgentSkillAssignment::where('agent_id', $agentId)
            ->where('skill_id', $skillId)
            ->delete() > 0;
    }
}
