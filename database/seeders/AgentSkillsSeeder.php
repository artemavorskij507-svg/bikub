<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Domain\AgentOS\Models\AgentSkill;
use App\Models\Domain\AgentOS\Models\AgentModelConfig;
use App\Modules\AgencyAgents\Models\Agent;
use App\Domain\AgentOS\Services\AgentSkillAssignmentService;

class AgentSkillsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Seeding agent skills...");

        $engineeringSkills = [
            ["name" => "React/Vue/Angular, UI implementation, performance", "category" => "engineering", "description" => "Modern web apps, pixel-perfect UIs, Core Web Vitals optimization"],
            ["name" => "API design, database architecture, scalability", "category" => "engineering", "description" => "Server-side systems, microservices, cloud infrastructure"],
            ["name" => "iOS/Android, React Native, Flutter", "category" => "engineering", "description" => "Native and cross-platform mobile applications"],
            ["name" => "ML models, deployment, AI integration", "category" => "engineering", "description" => "Machine learning features, data pipelines, AI-powered apps"],
            ["name" => "CI/CD, infrastructure automation, cloud ops", "category" => "engineering", "description" => "Pipeline development, deployment automation, monitoring"],
            ["name" => "Fast POC development, MVPs", "category" => "engineering", "description" => "Quick proof-of-concepts, hackathon projects, fast iteration"],
            ["name" => "Laravel/Livewire, advanced patterns", "category" => "engineering", "description" => "Complex implementations, architecture decisions"],
            ["name" => "Threat modeling, secure code review, security architecture", "category" => "engineering", "description" => "Application security, vulnerability assessment, security CI/CD"],
            ["name" => "Schema design, query optimization, indexing strategies", "category" => "engineering", "description" => "PostgreSQL/MySQL tuning, slow query debugging, migration planning"],
            ["name" => "Git workflow, branching strategies, conventional commits", "category" => "engineering", "description" => "Git workflow design, history cleanup, CI-friendly branch management"],
        ];

        $designSkills = [
            ["name" => "UI/UX Design", "category" => "design", "description" => "User interface and experience design"],
            ["name" => "Brand Identity", "category" => "design", "description" => "Brand strategy and visual identity"],
            ["name" => "Product Design", "category" => "design", "description" => "Product design and prototyping"],
            ["name" => "Graphic Design", "category" => "design", "description" => "Visual design and graphics"],
            ["name" => "Design Systems", "category" => "design", "description" => "Component libraries and design tokens"],
        ];

        $marketingSkills = [
            ["name" => "Content Marketing", "category" => "marketing", "description" => "Content strategy and creation"],
            ["name" => "SEO Optimization", "category" => "marketing", "description" => "Search engine optimization"],
            ["name" => "Social Media Marketing", "category" => "marketing", "description" => "Social media strategy and management"],
            ["name" => "Email Marketing", "category" => "marketing", "description" => "Email campaigns and automation"],
            ["name" => "Copywriting", "category" => "marketing", "description" => "Marketing copy and messaging"],
        ];

        $strategySkills = [
            ["name" => "Business Strategy", "category" => "strategy", "description" => "Strategic planning and analysis"],
            ["name" => "Product Strategy", "category" => "strategy", "description" => "Product roadmap and positioning"],
            ["name" => "Market Research", "category" => "strategy", "description" => "Market analysis and insights"],
        ];

        $salesSkills = [
            ["name" => "Sales Strategy", "category" => "sales", "description" => "Sales planning and execution"],
            ["name" => "Lead Generation", "category" => "sales", "description" => "Lead generation and qualification"],
            ["name" => "Customer Success", "category" => "sales", "description" => "Customer onboarding and retention"],
        ];

        $allSkills = array_merge($engineeringSkills, $designSkills, $marketingSkills, $strategySkills, $salesSkills);

        foreach ($allSkills as $skillData) {
            AgentSkill::firstOrCreate(
                ["name" => $skillData["name"], "category" => $skillData["category"]],
                ["description" => $skillData["description"], "is_active" => true]
            );
        }

        $this->command->info("Created " . count($allSkills) . " skills");

        $this->command->info("Assigning skills to agents...");
        $skillAssignment = app(AgentSkillAssignmentService::class);
        $stats = $skillAssignment->bulkAssignSkillsByAgentCategory();

        foreach ($stats as $category => $data) {
            $agentCount = $data["agents"];
            $skillCount = $data["skills_assigned"];
            $this->command->info("  {$category}: {$agentCount} agents, {$skillCount} skills assigned");
        }

        $this->command->info("Configuring AI models...");
        
        $directors = Agent::where("name", "like", "%director%")->orWhere("name", "like", "%orchestrator%")->get();
        
        foreach ($directors as $director) {
            AgentModelConfig::updateOrCreate(
                ["agent_id" => $director->id],
                ["model_name" => "opus-4.7", "temperature" => 0.7, "max_tokens" => 8192]
            );
        }
        $directorCount = $directors->count();
        $this->command->info("  Configured {$directorCount} director agents with Opus 4.7");

        $specialists = Agent::whereNotIn("id", $directors->pluck("id"))->where("status", "active")->get();
        
        foreach ($specialists as $specialist) {
            AgentModelConfig::updateOrCreate(
                ["agent_id" => $specialist->id],
                ["model_name" => "sonnet-4.5", "temperature" => 0.7, "max_tokens" => 4096]
            );
        }
        $specialistCount = $specialists->count();
        $this->command->info("  Configured {$specialistCount} specialist agents with Sonnet 4.5");

        $this->command->info("Agent skills seeding completed!");
    }
}
