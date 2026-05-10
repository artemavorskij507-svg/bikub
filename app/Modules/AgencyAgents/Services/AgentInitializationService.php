<?php

namespace App\Modules\AgencyAgents\Services;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentModuleAssignment;
use App\Modules\AgencyAgents\Models\OfficeZone;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AgentInitializationService
{
    private string $agentsPath;

    private array $categories = [];

    private array $excludedDirectories = [
        '.git',
        '.github',
        '.cursor',
        'scripts',
        'examples',
    ];

    public function __construct()
    {
        $this->agentsPath = $this->resolveAgentsPath();
        $this->categories = $this->resolveCategories();
    }

    public function initializeAllAgents(): array
    {
        if (! Schema::hasTable('agency_agents')) {
            return ['initialized' => [], 'errors' => [], 'total' => 0];
        }

        OfficeZone::initializeDefaultZones();
        $this->ensureExpandedOfficeZones();

        $initialized = [];
        $errors = [];

        foreach ($this->categories as $category) {
            $categoryPath = $this->agentsPath.'/'.$category;
            if (! File::exists($categoryPath)) {
                continue;
            }

            foreach (File::allFiles($categoryPath) as $fileInfo) {
                if ($fileInfo->getExtension() !== 'md') {
                    continue;
                }

                try {
                    $agent = $this->initializeAgentFromFile($fileInfo->getPathname(), $category);
                    if ($agent) {
                        $initialized[] = $agent;
                    }
                } catch (\Throwable $e) {
                    $errors[] = ['file' => $fileInfo->getPathname(), 'error' => $e->getMessage()];
                }
            }
        }

        return ['initialized' => $initialized, 'errors' => $errors, 'total' => count($initialized)];
    }

    public function ensureMinimumHeadcount(int $target = 110, ?string $apiKey = null): array
    {
        if (! Schema::hasTable('agency_agents')) {
            return [
                'status' => 'not_initialized',
                'created' => 0,
                'total' => 0,
                'target' => $target,
            ];
        }

        $this->initializeAllAgents();

        $target = max(100, $target);
        $existing = Agent::count();
        $created = 0;

        if ($existing < $target) {
            $created = $this->generateSyntheticAgents($target - $existing);
        }

        $this->assignOperationalProfiles();

        if (! empty($apiKey)) {
            $this->enableWorkspaceAccess($apiKey);
        }

        return [
            'status' => 'ready',
            'created' => $created,
            'total' => Agent::count(),
            'target' => $target,
        ];
    }

    public function initializeLogisticsOperationsAgents(int $limit = 24): array
    {
        $result = $this->initializeAllAgents();
        $operations = config('agency-agents.logistics_operations.roles', []);
        $createdAssignments = [];

        foreach (array_slice($operations, 0, max(20, $limit), true) as $slug => $definition) {
            $agent = Agent::query()->where('slug', $slug)->first();
            if (! $agent) {
                continue;
            }

            $agent->update([
                'status' => 'active',
                'current_activity' => 'logistics_orchestration',
                'status_message' => $definition['role'].' online',
                'metadata' => array_merge($agent->metadata ?? [], [
                    'orchestration_role' => $definition['role'],
                    'orchestration_modules' => $definition['modules'],
                    'access_level' => $definition['access_level'],
                ]),
            ]);

            foreach ($definition['modules'] as $moduleKey) {
                $createdAssignments[] = AgentModuleAssignment::updateOrCreate(
                    ['agent_id' => $agent->id, 'module_key' => $moduleKey],
                    [
                        'role' => $definition['role'],
                        'access_level' => $definition['access_level'],
                        'priority' => $definition['priority'] ?? 50,
                        'zones' => $definition['zones'] ?? [],
                        'routing_preferences' => $definition['routing_preferences'] ?? [],
                        'is_active' => true,
                        'metadata' => ['source' => 'agency-agents', 'launch_mode' => 'logistics_operations'],
                    ]
                );
            }
        }

        $result['module_assignments'] = $createdAssignments;
        $result['operations_total'] = count($createdAssignments);

        return $result;
    }

    public function initializeAgentFromFile(string $filePath, string $category): ?Agent
    {
        if (! File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $parsed = $this->parseAgentFile($content);
        if (! $parsed) {
            return null;
        }

        $slug = Str::slug($parsed['name']);
        $existingAgent = Agent::where('slug', $slug)->first();

        if ($existingAgent) {
            return $this->updateAgent($existingAgent, $parsed);
        }

        return $this->createAgent($parsed, $category, $filePath);
    }

    private function parseAgentFile(string $content): ?array
    {
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            return null;
        }

        return array_merge($this->parseYaml($matches[1]), $this->extractSections($matches[2]));
    }

    private function parseYaml(string $yaml): array
    {
        $result = [];
        foreach (explode("\n", $yaml) as $line) {
            if (preg_match('/^(\w+):\s*(.+)$/', $line, $matches)) {
                $result[$matches[1]] = trim($matches[2]);
            }
        }

        return $result;
    }

    private function extractSections(string $body): array
    {
        $sections = [
            'identity_memory' => '',
            'core_mission' => '',
            'critical_rules' => '',
            'technical_deliverables' => '',
            'workflow_process' => '',
            'success_metrics' => '',
        ];

        $patterns = [
            'identity_memory' => '/## .*?Identity.*?\n(.*?)(?=\n## |$)/s',
            'core_mission' => '/## .*?Mission.*?\n(.*?)(?=\n## |$)/s',
            'critical_rules' => '/## .*?Rules.*?\n(.*?)(?=\n## |$)/s',
            'technical_deliverables' => '/## .*?Deliverables.*?\n(.*?)(?=\n## |$)/s',
            'workflow_process' => '/## .*?Workflow.*?\n(.*?)(?=\n## |$)/s',
            'success_metrics' => '/## .*?Metrics.*?\n(.*?)(?=\n## |$)/s',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $sections[$key] = trim($matches[1]);
            }
        }

        return $sections;
    }

    private function createAgent(array $data, string $category, string $filePath): Agent
    {
        $position = $this->calculateAgentPosition($category);
        $profile = $this->buildOperationalProfile($category);

        $agent = Agent::create([
            'name' => $data['name'] ?? 'Unknown Agent',
            'slug' => Str::slug($data['name'] ?? 'unknown'),
            'description' => $data['description'] ?? '',
            'category' => $category,
            'color' => $data['color'] ?? 'gray',
            'emoji' => $data['emoji'] ?? 'agent',
            'vibe' => $data['vibe'] ?? '',
            'identity_memory' => $data['identity_memory'] ?? '',
            'core_mission' => $data['core_mission'] ?? '',
            'critical_rules' => $data['critical_rules'] ?? '',
            'technical_deliverables' => $data['technical_deliverables'] ?? '',
            'workflow_process' => $data['workflow_process'] ?? '',
            'success_metrics' => $data['success_metrics'] ?? '',
            'status' => 'active',
            'current_zone' => 'workspace',
            'position_x' => $position['x'],
            'position_y' => $position['y'],
            'avatar_direction' => 'down',
            'is_moving' => false,
            'current_activity' => 'working',
            'avatar_sprite' => $profile['pixel_style'],
            'metadata' => [
                'source_file' => $filePath,
                'initialized_at' => now()->toISOString(),
                'pixel_avatar' => $this->getPixelAvatarForCategory($category),
                'pixel_style' => $profile['pixel_style'],
                'team_key' => $profile['team_key'],
                'team_name' => $profile['team_name'],
                'behavior_pattern' => $profile['behavior_pattern'],
                'discipline' => $profile['discipline'],
            ],
        ]);

        if (Schema::hasTable('agency_agent_activities')) {
            AgentActivity::logMovement($agent, 'outside', 'workspace');
        }

        return $agent;
    }

    private function updateAgent(Agent $agent, array $data): Agent
    {
        $category = $agent->category;
        $profile = $this->buildOperationalProfile($category);

        $agent->update([
            'description' => $data['description'] ?? $agent->description,
            'color' => $data['color'] ?? $agent->color,
            'emoji' => $data['emoji'] ?? $agent->emoji,
            'vibe' => $data['vibe'] ?? $agent->vibe,
            'identity_memory' => $data['identity_memory'] ?? $agent->identity_memory,
            'core_mission' => $data['core_mission'] ?? $agent->core_mission,
            'critical_rules' => $data['critical_rules'] ?? $agent->critical_rules,
            'technical_deliverables' => $data['technical_deliverables'] ?? $agent->technical_deliverables,
            'workflow_process' => $data['workflow_process'] ?? $agent->workflow_process,
            'success_metrics' => $data['success_metrics'] ?? $agent->success_metrics,
            'avatar_sprite' => $profile['pixel_style'],
            'metadata' => array_merge($agent->metadata ?? [], [
                'last_updated' => now()->toISOString(),
                'pixel_style' => $profile['pixel_style'],
                'team_key' => $profile['team_key'],
                'team_name' => $profile['team_name'],
                'behavior_pattern' => $profile['behavior_pattern'],
                'discipline' => $profile['discipline'],
            ]),
        ]);

        return $agent;
    }

    private function calculateAgentPosition(string $category): array
    {
        $sector = Agent::CATEGORY_SECTORS[$category] ?? ['x_min' => 0, 'x_max' => 100, 'y_min' => 0, 'y_max' => 100];

        return [
            'x' => rand($sector['x_min'] + 20, $sector['x_max'] - 20),
            'y' => rand($sector['y_min'] + 20, $sector['y_max'] - 20),
        ];
    }

    private function getPixelAvatarForCategory(string $category): string
    {
        $avatars = [
            'academic' => 'academic',
            'design' => 'design',
            'engineering' => 'engineering',
            'game-development' => 'game-dev',
            'marketing' => 'marketing',
            'paid-media' => 'paid-media',
            'product' => 'product',
            'project-management' => 'pm',
            'sales' => 'sales',
            'specialized' => 'specialized',
            'strategy' => 'strategy',
            'spatial-computing' => 'spatial',
            'support' => 'specialized',
            'testing' => 'specialized',
        ];

        return $avatars[$category] ?? 'agent';
    }

    public function getAgentCount(): int
    {
        if (! Schema::hasTable('agency_agents')) {
            return 0;
        }

        return Agent::count();
    }

    public function getAgentsByCategory(): array
    {
        if (! Schema::hasTable('agency_agents')) {
            return [];
        }

        return Agent::selectRaw('category, COUNT(*) as count')->groupBy('category')->pluck('count', 'category')->toArray();
    }

    public function getAgentsByZone(): array
    {
        if (! Schema::hasTable('agency_agents')) {
            return [];
        }

        return Agent::selectRaw('current_zone, COUNT(*) as count')->groupBy('current_zone')->pluck('count', 'current_zone')->toArray();
    }

    private function generateSyntheticAgents(int $count): int
    {
        $created = 0;
        $roles = $this->syntheticRoleMatrix();

        for ($i = 0; $i < $count; $i++) {
            $role = $roles[$i % count($roles)];
            $category = $role['category'];
            $position = $this->calculateAgentPosition($category);

            $name = $role['title'].' Agent '.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $slug = Str::slug($role['title'].'-'.$i.'-'.Str::random(6));

            Agent::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $role['description'],
                'category' => $category,
                'color' => $role['color'],
                'emoji' => $role['emoji'],
                'vibe' => $role['vibe'],
                'identity_memory' => $role['identity_memory'],
                'core_mission' => $role['core_mission'],
                'critical_rules' => 'Maintain collaboration and transparent handoffs.',
                'technical_deliverables' => 'Deliver sprint-aligned work items with audit trail.',
                'workflow_process' => 'Plan > Execute > Review > Report',
                'success_metrics' => 'Lead time, quality score, collaboration score',
                'status' => 'active',
                'current_zone' => $role['preferred_zone'],
                'position_x' => $position['x'],
                'position_y' => $position['y'],
                'avatar_direction' => 'down',
                'is_moving' => false,
                'current_activity' => 'working',
                'status_message' => 'Ready for sprint execution',
                'avatar_sprite' => $role['pixel_style'],
                'metadata' => [
                    'synthetic' => true,
                    'pixel_avatar' => $this->getPixelAvatarForCategory($category),
                    'pixel_style' => $role['pixel_style'],
                    'team_key' => $role['team_key'],
                    'team_name' => $role['team_name'],
                    'behavior_pattern' => $role['behavior'],
                    'discipline' => $role['title'],
                    'initialized_at' => now()->toISOString(),
                ],
            ]);

            $created++;
        }

        return $created;
    }

    private function syntheticRoleMatrix(): array
    {
        return [
            ['title' => 'Backend Developer', 'category' => 'engineering', 'team_key' => 'builders', 'team_name' => 'Platform Builders', 'description' => 'Builds backend services and APIs.', 'color' => 'green', 'emoji' => 'dev', 'vibe' => 'precise', 'behavior' => 'maker', 'pixel_style' => 'openclaw-coder', 'preferred_zone' => 'open_space', 'identity_memory' => 'Laravel specialist', 'core_mission' => 'Ship reliable backend features.'],
            ['title' => 'Frontend Developer', 'category' => 'engineering', 'team_key' => 'builders', 'team_name' => 'Platform Builders', 'description' => 'Builds interactive office UI.', 'color' => 'blue', 'emoji' => 'ui', 'vibe' => 'curious', 'behavior' => 'maker', 'pixel_style' => 'openclaw-frontend', 'preferred_zone' => 'open_space', 'identity_memory' => 'Filament and Livewire specialist', 'core_mission' => 'Deliver responsive interfaces.'],
            ['title' => 'Designer', 'category' => 'design', 'team_key' => 'design-product', 'team_name' => 'Design Product Guild', 'description' => 'Designs user workflows and assets.', 'color' => 'purple', 'emoji' => 'design', 'vibe' => 'creative', 'behavior' => 'innovator', 'pixel_style' => 'openclaw-designer', 'preferred_zone' => 'brainstorm', 'identity_memory' => 'Product designer', 'core_mission' => 'Create coherent visual systems.'],
            ['title' => 'Data Analyst', 'category' => 'academic', 'team_key' => 'insights', 'team_name' => 'Insights Lab', 'description' => 'Analyzes delivery and office metrics.', 'color' => 'indigo', 'emoji' => 'data', 'vibe' => 'systematic', 'behavior' => 'analyst', 'pixel_style' => 'openclaw-analyst', 'preferred_zone' => 'server_room', 'identity_memory' => 'Analytics engineer', 'core_mission' => 'Provide actionable insights.'],
            ['title' => 'Project Manager', 'category' => 'project-management', 'team_key' => 'operations', 'team_name' => 'Operations Core', 'description' => 'Coordinates sprint execution.', 'color' => 'yellow', 'emoji' => 'pm', 'vibe' => 'structured', 'behavior' => 'coordinator', 'pixel_style' => 'openclaw-lead', 'preferred_zone' => 'meeting_room', 'identity_memory' => 'Sprint facilitator', 'core_mission' => 'Keep delivery on track.'],
            ['title' => 'QA Engineer', 'category' => 'specialized', 'team_key' => 'quality', 'team_name' => 'Quality Command', 'description' => 'Validates quality and regressions.', 'color' => 'red', 'emoji' => 'qa', 'vibe' => 'careful', 'behavior' => 'reviewer', 'pixel_style' => 'openclaw-qa', 'preferred_zone' => 'server_room', 'identity_memory' => 'Test automation specialist', 'core_mission' => 'Prevent regressions.'],
            ['title' => 'DevOps Engineer', 'category' => 'specialized', 'team_key' => 'operations', 'team_name' => 'Operations Core', 'description' => 'Maintains deployments and runtime.', 'color' => 'orange', 'emoji' => 'ops', 'vibe' => 'calm', 'behavior' => 'operator', 'pixel_style' => 'openclaw-devops', 'preferred_zone' => 'server_room', 'identity_memory' => 'Infrastructure owner', 'core_mission' => 'Maintain uptime and delivery flow.'],
            ['title' => 'Marketing Specialist', 'category' => 'marketing', 'team_key' => 'growth', 'team_name' => 'Growth Studio', 'description' => 'Plans campaigns and launches.', 'color' => 'pink', 'emoji' => 'mkt', 'vibe' => 'energetic', 'behavior' => 'strategist', 'pixel_style' => 'openclaw-marketing', 'preferred_zone' => 'open_space', 'identity_memory' => 'Campaign strategist', 'core_mission' => 'Drive engagement growth.'],
            ['title' => 'Copywriter', 'category' => 'marketing', 'team_key' => 'growth', 'team_name' => 'Growth Studio', 'description' => 'Creates product and campaign copy.', 'color' => 'cyan', 'emoji' => 'copy', 'vibe' => 'expressive', 'behavior' => 'writer', 'pixel_style' => 'openclaw-writer', 'preferred_zone' => 'open_space', 'identity_memory' => 'Conversion copywriter', 'core_mission' => 'Craft clear messaging.'],
            ['title' => 'Legal Advisor', 'category' => 'strategy', 'team_key' => 'governance', 'team_name' => 'Governance Board', 'description' => 'Reviews legal and compliance risks.', 'color' => 'gray', 'emoji' => 'legal', 'vibe' => 'formal', 'behavior' => 'guardian', 'pixel_style' => 'openclaw-legal', 'preferred_zone' => 'meeting_room', 'identity_memory' => 'Regulatory specialist', 'core_mission' => 'Keep operations compliant.'],
            ['title' => 'HR Partner', 'category' => 'sales', 'team_key' => 'people', 'team_name' => 'People Team', 'description' => 'Supports staffing and wellbeing.', 'color' => 'violet', 'emoji' => 'hr', 'vibe' => 'supportive', 'behavior' => 'coach', 'pixel_style' => 'openclaw-hr', 'preferred_zone' => 'relax_zone', 'identity_memory' => 'Talent coordinator', 'core_mission' => 'Support team health and staffing.'],
            ['title' => 'Finance Controller', 'category' => 'strategy', 'team_key' => 'finance', 'team_name' => 'Finance Desk', 'description' => 'Tracks budgets and spend.', 'color' => 'lime', 'emoji' => 'fin', 'vibe' => 'analytical', 'behavior' => 'controller', 'pixel_style' => 'openclaw-finance', 'preferred_zone' => 'meeting_room', 'identity_memory' => 'Financial analyst', 'core_mission' => 'Optimize budget and ROI.'],
        ];
    }

    private function assignOperationalProfiles(): void
    {
        Agent::query()->chunkById(200, function ($agents): void {
            foreach ($agents as $agent) {
                $profile = $this->buildOperationalProfile($agent->category);
                $meta = is_array($agent->metadata) ? $agent->metadata : [];

                $agent->update([
                    'avatar_sprite' => $profile['pixel_style'],
                    'metadata' => array_merge($meta, [
                        'team_key' => $profile['team_key'],
                        'team_name' => $profile['team_name'],
                        'behavior_pattern' => $profile['behavior_pattern'],
                        'pixel_style' => $profile['pixel_style'],
                        'discipline' => $profile['discipline'],
                    ]),
                ]);
            }
        });
    }

    private function enableWorkspaceAccess(string $apiKey): void
    {
        $hash = hash('sha256', $apiKey);

        Agent::query()->chunkById(200, function ($agents) use ($hash): void {
            foreach ($agents as $agent) {
                $meta = is_array($agent->metadata) ? $agent->metadata : [];
                $agent->update([
                    'metadata' => array_merge($meta, [
                        'workspace_api_enabled' => true,
                        'workspace_api_key_hash' => $hash,
                        'workspace_api_header' => 'X-API-Key',
                        'workspace_api_scopes' => ['office.read', 'office.write', 'office.broadcast'],
                    ]),
                ]);
            }
        });
    }

    private function buildOperationalProfile(string $category): array
    {
        return match ($category) {
            'engineering', 'game-development' => [
                'team_key' => 'builders',
                'team_name' => 'Platform Builders',
                'behavior_pattern' => 'maker',
                'pixel_style' => 'openclaw-coder',
                'discipline' => 'Engineering',
            ],
            'design', 'product' => [
                'team_key' => 'design-product',
                'team_name' => 'Design Product Guild',
                'behavior_pattern' => 'innovator',
                'pixel_style' => 'openclaw-designer',
                'discipline' => 'Design Product',
            ],
            'project-management', 'strategy' => [
                'team_key' => 'operations',
                'team_name' => 'Operations Core',
                'behavior_pattern' => 'coordinator',
                'pixel_style' => 'openclaw-lead',
                'discipline' => 'Operations',
            ],
            'marketing', 'sales', 'paid-media' => [
                'team_key' => 'growth',
                'team_name' => 'Growth Studio',
                'behavior_pattern' => 'strategist',
                'pixel_style' => 'openclaw-marketing',
                'discipline' => 'Growth',
            ],
            'testing' => [
                'team_key' => 'quality',
                'team_name' => 'Quality Command',
                'behavior_pattern' => 'reviewer',
                'pixel_style' => 'openclaw-qa',
                'discipline' => 'Quality Assurance',
            ],
            'support' => [
                'team_key' => 'operations',
                'team_name' => 'Support Operations',
                'behavior_pattern' => 'operator',
                'pixel_style' => 'openclaw-specialist',
                'discipline' => 'Support',
            ],
            default => [
                'team_key' => 'specialists',
                'team_name' => 'Specialists Hub',
                'behavior_pattern' => 'balanced',
                'pixel_style' => 'openclaw-specialist',
                'discipline' => 'Specialized',
            ],
        };
    }

    private function resolveAgentsPath(): string
    {
        $candidates = [
            config('agency-agents.paths.agents_source'),
            base_path('agency-agents'),
            dirname(base_path()).'/agency-agents',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && File::isDirectory($candidate)) {
                return $candidate;
            }
        }

        return base_path('agency-agents');
    }

    private function resolveCategories(): array
    {
        if (! File::isDirectory($this->agentsPath)) {
            return [];
        }

        return collect(File::directories($this->agentsPath))
            ->map(fn (string $path): string => basename($path))
            ->filter(function (string $directory): bool {
                if (in_array($directory, $this->excludedDirectories, true)) {
                    return false;
                }

                return collect(File::allFiles($this->agentsPath.'/'.$directory))
                    ->contains(fn ($file) => $file->getExtension() === 'md');
            })
            ->sort()
            ->values()
            ->toArray();
    }

    private function ensureExpandedOfficeZones(): void
    {
        if (! Schema::hasTable('agency_office_zones')) {
            return;
        }

        $zones = [
            [
                'name' => 'open_space',
                'display_name' => 'Open Space',
                'icon' => 'open',
                'color' => '#dbeafe',
                'bounds' => ['x_min' => 0, 'x_max' => 480, 'y_min' => 0, 'y_max' => 320],
                'capacity' => 70,
                'amenities' => ['desks', 'workstations', 'pairing_tables'],
            ],
            [
                'name' => 'meeting_room',
                'display_name' => 'Meeting Room',
                'icon' => 'meeting',
                'color' => '#ffedd5',
                'bounds' => ['x_min' => 500, 'x_max' => 800, 'y_min' => 0, 'y_max' => 180],
                'capacity' => 20,
                'amenities' => ['conference_table', 'screen', 'whiteboard'],
            ],
            [
                'name' => 'server_room',
                'display_name' => 'Server Room',
                'icon' => 'server',
                'color' => '#e2e8f0',
                'bounds' => ['x_min' => 500, 'x_max' => 800, 'y_min' => 200, 'y_max' => 320],
                'capacity' => 18,
                'amenities' => ['racks', 'monitoring_wall', 'incident_desk'],
            ],
            [
                'name' => 'kitchen',
                'display_name' => 'Kitchen',
                'icon' => 'kitchen',
                'color' => '#fef3c7',
                'bounds' => ['x_min' => 0, 'x_max' => 260, 'y_min' => 340, 'y_max' => 600],
                'capacity' => 30,
                'amenities' => ['coffee_station', 'tables', 'snacks'],
            ],
            [
                'name' => 'relax_zone',
                'display_name' => 'Relax Zone',
                'icon' => 'relax',
                'color' => '#fce7f3',
                'bounds' => ['x_min' => 280, 'x_max' => 800, 'y_min' => 340, 'y_max' => 600],
                'capacity' => 42,
                'amenities' => ['sofas', 'quiet_corner', 'brainstorm_wall'],
            ],
        ];

        foreach ($zones as $zone) {
            OfficeZone::updateOrCreate(['name' => $zone['name']], $zone);
        }
    }
}

