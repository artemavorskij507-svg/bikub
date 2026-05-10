<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiAgent;

class AiAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coreAgents = [
            'Agents Orchestrator', 'Project Shepherd', 'Software Architect', 'Backend Architect',
            'Senior Developer', 'Frontend Developer', 'Product Manager', 'UI Designer',
            'AI Engineer', 'MCP Builder', 'Analytics Reporter', 'Growth Hacker',
            'SEO Specialist', 'Support Responder', 'Reality Checker', 'Security Engineer',
            'Legal Compliance Checker', 'Infrastructure Maintainer'
        ];

        $agents = [
            // Командный центр
            ['name' => 'Agents Orchestrator', 'department' => 'Command Center', 'permissions' => 'approval-required'],
            ['name' => 'Project Shepherd', 'department' => 'Command Center', 'permissions' => 'read-only'],
            ['name' => 'Studio Producer', 'department' => 'Command Center', 'permissions' => 'read-only'],
            ['name' => 'Studio Operations', 'department' => 'Command Center', 'permissions' => 'read-only'],
            ['name' => 'Senior Project Manager', 'department' => 'Command Center', 'permissions' => 'read-only'],
            ['name' => 'Automation Governance Architect', 'department' => 'Command Center', 'permissions' => 'break-glass'],
            ['name' => 'Agentic Identity & Trust Architect', 'department' => 'Command Center', 'permissions' => 'break-glass'],
            ['name' => 'ZK Steward', 'department' => 'Command Center', 'permissions' => 'approval-required'],

            // Платформа и разработка
            ['name' => 'Software Architect', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'Backend Architect', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'Senior Developer', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'Frontend Developer', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'CMS Developer', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'Database Optimizer', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'DevOps Automator', 'department' => 'Platform & Dev', 'permissions' => 'approval-required'],
            ['name' => 'SRE', 'department' => 'Platform & Dev', 'permissions' => 'break-glass'],
            ['name' => 'Security Engineer', 'department' => 'Platform & Dev', 'permissions' => 'break-glass'],
            ['name' => 'Git Workflow Master', 'department' => 'Platform & Dev', 'permissions' => 'read-only'],
            ['name' => 'Code Reviewer', 'department' => 'Platform & Dev', 'permissions' => 'read-only'],
            ['name' => 'Codebase Onboarding Engineer', 'department' => 'Platform & Dev', 'permissions' => 'read-only'],

            // Продукт, UX и бренд
            ['name' => 'Product Manager', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'Sprint Prioritizer', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'Trend Researcher', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'Feedback Synthesizer', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'Behavioral Nudge Engine', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'UI Designer', 'department' => 'Product & UX', 'permissions' => 'draft-only'],
            ['name' => 'UX Researcher', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'UX Architect', 'department' => 'Product & UX', 'permissions' => 'read-only'],
            ['name' => 'Brand Guardian', 'department' => 'Product & UX', 'permissions' => 'approval-required'],
            ['name' => 'Visual Storyteller', 'department' => 'Product & UX', 'permissions' => 'draft-only'],

            // AI, данные и интеграции
            ['name' => 'AI Engineer', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'Autonomous Optimization Architect', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'Data Engineer', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'AI Data Remediation Engineer', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'MCP Builder', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'LSP/Index Engineer', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'Email Intelligence Engineer', 'department' => 'AI & Data', 'permissions' => 'approval-required'],
            ['name' => 'Analytics Reporter', 'department' => 'AI & Data', 'permissions' => 'read-only'],

            // Рост, SEO и платный трафик
            ['name' => 'Growth Hacker', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'Content Creator', 'department' => 'Growth & SEO', 'permissions' => 'draft-only'],
            ['name' => 'SEO Specialist', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'AI Citation Strategist', 'department' => 'Growth & SEO', 'permissions' => 'read-only'],
            ['name' => 'Tracking & Measurement Specialist', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'PPC Campaign Strategist', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'Paid Media Auditor', 'department' => 'Growth & SEO', 'permissions' => 'read-only'],
            ['name' => 'Search Query Analyst', 'department' => 'Growth & SEO', 'permissions' => 'read-only'],
            ['name' => 'Ad Creative Strategist', 'department' => 'Growth & SEO', 'permissions' => 'draft-only'],
            ['name' => 'Paid Social Strategist', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'Social Media Strategist', 'department' => 'Growth & SEO', 'permissions' => 'approval-required'],
            ['name' => 'Video Optimization Specialist', 'department' => 'Growth & SEO', 'permissions' => 'draft-only'],

            // Соцсети и контент-экзекьюшн
            ['name' => 'Instagram Curator', 'department' => 'Social Media', 'permissions' => 'approval-required'],
            ['name' => 'TikTok Strategist', 'department' => 'Social Media', 'permissions' => 'approval-required'],
            ['name' => 'Carousel Growth Engine', 'department' => 'Social Media', 'permissions' => 'draft-only'],

            // Продажи, партнёры и B2B
            ['name' => 'Account Strategist', 'department' => 'Sales & B2B', 'permissions' => 'approval-required'],
            ['name' => 'Sales Engineer', 'department' => 'Sales & B2B', 'permissions' => 'approval-required'],
            ['name' => 'Deal Strategist', 'department' => 'Sales & B2B', 'permissions' => 'approval-required'],
            ['name' => 'Pipeline Analyst', 'department' => 'Sales & B2B', 'permissions' => 'read-only'],
            ['name' => 'Outbound Strategist', 'department' => 'Sales & B2B', 'permissions' => 'draft-only'],

            // QA, риск и контроль качества
            ['name' => 'Reality Checker', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'Evidence Collector', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'Test Results Analyzer', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'Performance Benchmarker', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'API Tester', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'Accessibility Auditor', 'department' => 'QA & Risk', 'permissions' => 'read-only'],
            ['name' => 'Legal Compliance Checker', 'department' => 'QA & Risk', 'permissions' => 'approval-required'],
            ['name' => 'Compliance Auditor', 'department' => 'QA & Risk', 'permissions' => 'read-only'],

            // Операции сервиса и клиентская работа
            ['name' => 'Support Responder', 'department' => 'Operations', 'permissions' => 'draft-only'],
            ['name' => 'Customer Service', 'department' => 'Operations', 'permissions' => 'draft-only'],
            ['name' => 'Hospitality Guest Services', 'department' => 'Operations', 'permissions' => 'read-only'],
            ['name' => 'Supply Chain Strategist', 'department' => 'Operations', 'permissions' => 'read-only'],
            ['name' => 'Infrastructure Maintainer', 'department' => 'Operations', 'permissions' => 'approval-required'],
            ['name' => 'Identity Graph Operator', 'department' => 'Operations', 'permissions' => 'approval-required'],

            // Custom Bikube Agents
            ['name' => 'Dispatch & Routing Coordinator', 'department' => 'Custom Bikube', 'permissions' => 'approval-required'],
            ['name' => 'Marketplace Moderation Agent', 'department' => 'Custom Bikube', 'permissions' => 'approval-required'],
            ['name' => 'Vendor Onboarding & Quality Agent', 'department' => 'Custom Bikube', 'permissions' => 'approval-required'],
            ['name' => 'GLF Mat Menu & Promo Manager', 'department' => 'Custom Bikube', 'permissions' => 'approval-required'],
            ['name' => 'Geo Pricing & Zone Planner', 'department' => 'Custom Bikube', 'permissions' => 'approval-required'],
        ];

        foreach ($agents as $agentData) {
            $isCore = in_array($agentData['name'], $coreAgents);
            
            AiAgent::create([
                'name' => $agentData['name'],
                'department' => $agentData['department'],
                'status' => $isCore ? 'active' : 'standby',
                'is_core' => $isCore,
                'permissions_level' => $agentData['permissions'],
            ]);
        }
    }
}
