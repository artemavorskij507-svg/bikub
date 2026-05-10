<?php

namespace App\Domain\AgentOS\Services;

use App\Models\Domain\AgentOS\Models\AgentSkill;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SkillsLibraryService
{
    public function loadSkillsFromAgencyAgents(string $agentsPath): array
    {
        $skills = [];
        
        if (!File::exists($agentsPath)) {
            Log::warning('Agency agents path not found', ['path' => $agentsPath]);
            return $skills;
        }

        $files = File::allFiles($agentsPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($file->getPathname());
            $parsed = $this->parseAgentFile($content);
            
            if (!empty($parsed['skills'])) {
                $category = $parsed['category'] ?? 'general';
                
                foreach ($parsed['skills'] as $skillName) {
                    $skills[] = [
                        'name' => $skillName,
                        'category' => $category,
                        'description' => $parsed['description'] ?? '',
                    ];
                }
            }
        }

        return $skills;
    }

    protected function parseAgentFile(string $content): array
    {
        $data = [];
        
        // Extract frontmatter
        if (preg_match('/^---\s*\n(.*?)\n---/s', $content, $matches)) {
            $frontmatter = $matches[1];
            
            // Parse YAML-like frontmatter
            $lines = explode("\n", $frontmatter);
            foreach ($lines as $line) {
                if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $m)) {
                    $data[$m[1]] = trim($m[2], '"\'');
                }
            }
        }

        // Extract skills from content
        $skills = [];
        if (preg_match('/##\s*Skills.*?\n(.*?)(?=\n##|\z)/s', $content, $matches)) {
            $skillsSection = $matches[1];
            preg_match_all('/[-*]\s*(.+)/', $skillsSection, $skillMatches);
            $skills = array_map('trim', $skillMatches[1]);
        }

        $data['skills'] = $skills;
        
        return $data;
    }

    public function createSkill(array $data): AgentSkill
    {
        return AgentSkill::create([
            'name' => $data['name'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'prompt_template' => $data['prompt_template'] ?? null,
            'tools' => $data['tools'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function getSkillsByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return AgentSkill::where('category', $category)
            ->where('is_active', true)
            ->get();
    }

    public function seedSkillsFromEngenerTxt(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $skills = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'Agent') === false) {
                continue;
            }

            // Parse format: Agent	Specialty	When to Use
            $parts = preg_split('/\t+/', $line);
            if (count($parts) >= 3) {
                $agentName = trim($parts[0]);
                $specialty = trim($parts[1]);
                $whenToUse = trim($parts[2]);
                
                // Skip header row
                if ($agentName === 'Agent') {
                    continue;
                }

                $skills[] = [
                    'name' => $specialty,
                    'category' => 'engineering',
                    'description' => $whenToUse,
                ];
            }
        }

        return $skills;
    }
}
