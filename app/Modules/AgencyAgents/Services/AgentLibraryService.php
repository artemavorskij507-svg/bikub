<?php

namespace App\Modules\AgencyAgents\Services;

use App\Modules\AgencyAgents\Models\Agent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AgentLibraryService
{
    protected string $libraryPath = 'C:\\home\\vscode\\agency-agents';

    public function syncAll(): int
    {
        $synchronized = 0;
        $syncedSlugs = [];
        
        if (!File::exists($this->libraryPath)) {
            return 0;
        }

        $directories = File::directories($this->libraryPath);
        foreach ($directories as $dir) {
            if (Str::startsWith(basename($dir), '.')) {
                continue;
            }
            if (basename($dir) === 'scripts') continue;

            $files = File::files($dir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $agent = $this->syncAgentFile($file->getPathname(), basename($dir));
                    $syncedSlugs[] = $agent->slug;
                    $synchronized++;
                }
            }
        }
        
        // Remove old agents that are no longer in the markdown library
        if (count($syncedSlugs) > 0) {
            Agent::whereNotIn('slug', $syncedSlugs)->delete();
        }
        
        return $synchronized;
    }

    public function syncAgentFile(string $filePath, string $category): Agent
    {
        $content = File::get($filePath);
        
        // Parse YAML frontmatter
        $frontmatter = [];
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $frontmatter[trim($key)] = trim($value);
                }
            }
            $content = str_replace($matches[0], '', $content);
        }
        
        $slug = Str::slug(str_replace('.md', '', basename($filePath)));
        
        $sections = [
            'identity_memory' => $this->extractSection($content, 'Your Identity & Memory'),
            'core_mission' => $this->extractSection($content, 'Your Core Mission'),
            'critical_rules' => $this->extractSection($content, 'Critical Rules'),
            'technical_deliverables' => $this->extractSection($content, 'Technical Deliverables'),
            'workflow_process' => $this->extractSection($content, 'Workflow Process'),
            'success_metrics' => $this->extractSection($content, 'Success Metrics'),
        ];
        
        return Agent::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $frontmatter['name'] ?? Str::title(str_replace('-', ' ', basename($filePath, '.md'))),
                'description' => $frontmatter['description'] ?? '',
                'category' => $category,
                'color' => $frontmatter['color'] ?? 'slate',
                'emoji' => $frontmatter['emoji'] ?? '🤖',
                'vibe' => $frontmatter['vibe'] ?? '',
                'identity_memory' => $sections['identity_memory'],
                'core_mission' => $sections['core_mission'],
                'critical_rules' => $sections['critical_rules'],
                'technical_deliverables' => $sections['technical_deliverables'],
                'workflow_process' => $sections['workflow_process'],
                'success_metrics' => $sections['success_metrics'],
            ]
        );
    }
    
    protected function extractSection(string $content, string $headerPartial): ?string
    {
        // Matches the header, and then captures all content up to the next # header or end of file.
        $pattern = '/#.*?' . preg_quote($headerPartial, '/') . '.*?\n(.*?)(?=\n#|$)/is';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
