<?php

namespace App\Console\Commands;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportAgentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtual-office:import-agents 
                            {--path=agency-agents : Path to agency-agents directory}
                            {--force : Force import even if agents already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import agents from agency-agents directory into virtual office';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = base_path($this->option('path'));
        $force = $this->option('force');

        if (!File::exists($path)) {
            $this->error("Directory not found: {$path}");
            return 1;
        }

        $this->info("Importing agents from: {$path}");

        // Get all category directories
        $categories = File::directories($path);
        $totalAgents = 0;
        $importedAgents = 0;

        foreach ($categories as $categoryPath) {
            $categoryName = basename($categoryPath);
            
            // Skip examples directory
            if ($categoryName === 'examples') {
                continue;
            }

            $this->info("Processing category: {$categoryName}");

            // Get all agent files in category
            $agentFiles = File::glob("{$categoryPath}/*.md");

            foreach ($agentFiles as $agentFile) {
                $totalAgents++;
                $agentName = pathinfo($agentFile, PATHINFO_FILENAME);
                
                // Parse agent file
                $agentData = $this->parseAgentFile($agentFile, $categoryName);
                
                if (!$agentData) {
                    $this->warn("Failed to parse: {$agentFile}");
                    continue;
                }

                // Get or create category
                $category = $this->getOrCreateCategory($categoryName);

                // Create or update agent
                $agent = Agent::updateOrCreate(
                    ['slug' => $agentData['slug']],
                    [
                        'name' => $agentData['name'],
                        'role' => $agentData['role'],
                        'description' => $agentData['description'],
                        'category_id' => $category->id,
                        'status' => 'offline',
                        'skills' => $agentData['skills'] ?? [],
                        'metadata' => $agentData['metadata'] ?? [],
                        'position_x' => rand(50, 750),
                        'position_y' => rand(50, 550),
                    ]
                );

                $importedAgents++;
                $this->line("  ✓ Imported: {$agent->name}");
            }
        }

        $this->info("");
        $this->info("Import completed!");
        $this->info("Total agents found: {$totalAgents}");
        $this->info("Agents imported: {$importedAgents}");

        return 0;
    }

    /**
     * Parse agent markdown file
     */
    protected function parseAgentFile($filePath, $category)
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $data = [
            'name' => '',
            'slug' => '',
            'role' => '',
            'description' => '',
            'skills' => [],
            'metadata' => [],
        ];

        // Extract name from filename
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $data['slug'] = $filename;
        $data['name'] = $this->formatAgentName($filename);

        // Parse markdown content
        $inFrontmatter = false;
        $frontmatter = [];
        $contentLines = [];

        foreach ($lines as $line) {
            if (trim($line) === '---') {
                $inFrontmatter = !$inFrontmatter;
                continue;
            }

            if ($inFrontmatter) {
                // Parse frontmatter
                if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $matches)) {
                    $frontmatter[$matches[1]] = $matches[2];
                }
            } else {
                $contentLines[] = $line;
            }
        }

        // Extract role from frontmatter or first heading
        if (isset($frontmatter['role'])) {
            $data['role'] = $frontmatter['role'];
        } else {
            // Try to find role in first heading
            foreach ($lines as $line) {
                if (strpos($line, '# ') === 0) {
                    $data['role'] = substr($line, 2);
                    break;
                }
            }
        }

        // Extract description from first paragraph
        $content = implode("\n", $contentLines);
        if (preg_match('/^# .+\n\n(.+?)(\n\n|$)/s', $content, $matches)) {
            $data['description'] = trim($matches[1]);
        }

        // Extract skills from content
        if (preg_match('/## Skills\n\n(.+?)(\n\n|$)/s', $content, $matches)) {
            $skillsText = trim($matches[1]);
            $data['skills'] = array_map('trim', explode("\n", $skillsText));
            $data['skills'] = array_filter($data['skills'], function($skill) {
                return !empty($skill) && strpos($skill, '-') === 0;
            });
            $data['skills'] = array_map(function($skill) {
                return ltrim($skill, '- ');
            }, $data['skills']);
        }

        // Store metadata
        $data['metadata'] = [
            'category' => $category,
            'source_file' => $filePath,
            'frontmatter' => $frontmatter,
        ];

        return $data;
    }

    /**
     * Format agent name from slug
     */
    protected function formatAgentName($slug)
    {
        // Remove category prefix
        $name = preg_replace('/^[a-z]+-/', '', $slug);
        
        // Convert to title case
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        
        return $name;
    }

    /**
     * Get or create category
     */
    protected function getOrCreateCategory($categoryName)
    {
        $categoryColors = [
            'engineering' => '#3B82F6',
            'design' => '#8B5CF6',
            'marketing' => '#EC4899',
            'sales' => '#10B981',
            'project-management' => '#F59E0B',
            'testing' => '#EF4444',
            'specialized' => '#6366F1',
            'game-development' => '#14B8A6',
        ];

        $category = Category::firstOrCreate(
            ['slug' => $categoryName],
            [
                'name' => $this->formatCategoryName($categoryName),
                'color' => $categoryColors[$categoryName] ?? '#6B7280',
                'description' => ucfirst($categoryName) . ' agents',
            ]
        );

        return $category;
    }

    /**
     * Format category name
     */
    protected function formatCategoryName($slug)
    {
        return ucwords(str_replace('-', ' ', $slug));
    }
}
