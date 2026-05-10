<?php

namespace App\Domain\AgentOS\Services;

use App\Domain\AgentOS\Models\AgentRun;
use App\Domain\AgentOS\Models\AgentStep;
use App\Modules\AgencyAgents\Models\Agent;
use Illuminate\Support\Facades\Log;

class DirectorAgentService
{
    protected ModelRouterService $modelRouter;
    protected AgentSkillAssignmentService $skillAssignment;
    protected RunOrchestratorService $orchestrator;

    public function __construct(
        ModelRouterService $modelRouter,
        AgentSkillAssignmentService $skillAssignment,
        RunOrchestratorService $orchestrator
    ) {
        $this->modelRouter = $modelRouter;
        $this->skillAssignment = $skillAssignment;
        $this->orchestrator = $orchestrator;
    }

    public function analyzeRequest(string $userRequest, ?int $tenantId = null): array
    {
        $directorAgent = $this->getDirectorAgent($tenantId);
        
        if (!$directorAgent) {
            return [
                'success' => false,
                'error' => 'No director agent configured',
            ];
        }

        $analysisPrompt = $this->buildAnalysisPrompt($userRequest);
        
        $response = $this->modelRouter->callModel(
            $directorAgent->id,
            $analysisPrompt,
            ['system_prompt' => $this->getDirectorSystemPrompt()]
        );

        if (!$response['success']) {
            return $response;
        }

        return $this->parseAnalysisResponse($response['content']);
    }

    protected function buildAnalysisPrompt(string $userRequest): string
    {
        return <<<PROMPT
Analyze the following user request and determine:
1. What skills are required to complete this task
2. Which agent categories should be involved (engineering, design, marketing, etc.)
3. The sequence of steps needed
4. Estimated complexity (1-5)

User Request:
{$userRequest}

Respond in JSON format:
{
    required_skills: [skill1, skill2],
    required_categories: [category1, category2],
    steps: [
        {description: step description, category: category, dependencies: []}
    ],
    complexity: 3
}
PROMPT;
    }

    protected function parseAnalysisResponse(string $content): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return [
                    'success' => true,
                    'analysis' => $json,
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Failed to parse analysis response',
            'raw_content' => $content,
        ];
    }

    public function assembleTeam(array $analysis, ?int $tenantId = null): array
    {
        $requiredCategories = $analysis['required_categories'] ?? [];
        $team = [];

        foreach ($requiredCategories as $category) {
            $agents = Agent::where('category', $category)
                ->where('status', 'active')
                ->limit(5)
                ->get();

            foreach ($agents as $agent) {
                $team[] = [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'category' => $agent->category,
                    'skills' => $this->skillAssignment->getAgentSkills($agent->id),
                ];
            }
        }

        return $team;
    }

    public function createAgentRun(string $userRequest, array $analysis, array $team, ?int $tenantId = null): AgentRun
    {
        $run = AgentRun::create([
            'name' => 'Multi-Agent Task: ' . substr($userRequest, 0, 50),
            'description' => $userRequest,
            'status' => 'PLANNING',
            'metadata' => [
                'tenant_id' => $tenantId,
                'analysis' => $analysis,
                'team' => $team,
                'created_by' => 'director',
            ],
        ]);

        // Create steps from analysis
        $steps = $analysis['steps'] ?? [];
        foreach ($steps as $index => $stepData) {
            $agentForStep = $this->selectAgentForStep($stepData, $team);
            
            AgentStep::create([
                'agent_run_id' => $run->id,
                'agent_id' => $agentForStep['agent_id'] ?? null,
                'name' => $stepData['description'],
                'description' => $stepData['description'],
                'status' => 'PENDING',
                'order' => $index + 1,
                'dependencies' => $stepData['dependencies'] ?? [],
                'metadata' => [
                    'category' => $stepData['category'] ?? 'general',
                ],
            ]);
        }

        return $run;
    }

    protected function selectAgentForStep(array $stepData, array $team): ?array
    {
        $category = $stepData['category'] ?? null;
        
        if (!$category) {
            return $team[0] ?? null;
        }

        foreach ($team as $agent) {
            if ($agent['category'] === $category) {
                return $agent;
            }
        }

        return $team[0] ?? null;
    }

    public function executeRun(AgentRun $run): AgentRun
    {
        return $this->orchestrator->run($run, [
            'max_iterations' => 50,
        ]);
    }

    protected function getDirectorAgent(?int $tenantId = null): ?Agent
    {
        // Try to find director agent for tenant
        if ($tenantId) {
            $team = \App\Models\Domain\AgentOS\Models\TenantAgentTeam::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();
            
            if ($team && $team->director_agent_id) {
                return Agent::find($team->director_agent_id);
            }
        }

        // Fallback: find any agent with 'director' or 'orchestrator' in name
        return Agent::where('name', 'like', '%director%')
            ->orWhere('name', 'like', '%orchestrator%')
            ->first();
    }

    protected function getDirectorSystemPrompt(): string
    {
        return <<<PROMPT
You are the Director Agent - an AI orchestrator responsible for analyzing complex tasks and coordinating teams of specialized agents.

Your responsibilities:
1. Analyze user requests to understand requirements
2. Identify required skills and agent categories
3. Break down tasks into sequential steps
4. Assign appropriate agents to each step
5. Monitor progress and handle failures

You have access to agents across these categories:
- Engineering (Backend, Frontend, DevOps, Security, etc.)
- Design (UI/UX, Brand, Graphics, Product)
- Marketing (Content, SEO, Social, Email)
- Strategy, Sales, Support, and more

Always respond with structured JSON for analysis tasks.
Be thorough but efficient in your planning.
PROMPT;
    }
}
