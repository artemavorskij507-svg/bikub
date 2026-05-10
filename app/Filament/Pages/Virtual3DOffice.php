<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;
use Livewire\WithPagination;

class Virtual3DOffice extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Virtual 3D Office';
    protected static ?string $title = 'Virtual 3D Office';
    protected static ?string $slug = 'virtual-3d-office';
    protected static ?int $navigationSort = 100;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.virtual-3d-office';

    public $agents = [];
    public $selectedAgent = null;
    public $viewMode = '3d'; // '3d' or 'list'
    public $filterCategory = 'all';
    public $filterStatus = 'all';
    public $showCommunications = false;
    public $systemOverview = [];

    public function mount(): void
    {
        $this->loadAgents();
        $this->loadSystemOverview();
    }

    public function loadAgents(): void
    {
        $query = Agent::query();

        if ($this->filterCategory !== 'all') {
            $query->where('category', $this->filterCategory);
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        $this->agents = $query->get()->map(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'emoji' => $agent->emoji,
                'category' => $agent->category,
                'status' => $agent->status,
                'color' => $agent->color,
                'performance_score' => $agent->performance_score,
                'tasks_completed' => $agent->tasks_completed,
                'position' => [
                    'x' => $agent->position_x,
                    'y' => $agent->position_y,
                    'z' => $agent->position_z,
                ],
                'avatar_url' => $agent->avatar_url,
                'last_active' => $agent->last_active_at?->diffForHumans(),
            ];
        })->toArray();
    }

    public function loadSystemOverview(): void
    {
        $service = app(AgentMonitoringService::class);
        $this->systemOverview = $service->getSystemOverview();
    }

    public function selectAgent(int $agentId): void
    {
        $this->selectedAgent = Agent::with(['tasks', 'communications'])->find($agentId);
        $this->showCommunications = false;
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === '3d' ? 'list' : '3d';
    }

    public function filterByCategory(string $category): void
    {
        $this->filterCategory = $category;
        $this->loadAgents();
    }

    public function filterByStatus(string $status): void
    {
        $this->filterStatus = $status;
        $this->loadAgents();
    }

    public function toggleCommunications(): void
    {
        $this->showCommunications = !$this->showCommunications;
    }

    public function refreshData(): void
    {
        $this->loadAgents();
        $this->loadSystemOverview();
    }

    public function updateAgentPosition(int $agentId, float $x, float $y, float $z): void
    {
        $agent = Agent::find($agentId);
        if ($agent) {
            $agent->updatePosition($x, $y, $z);
            $this->loadAgents();
        }
    }

    public function assignTask(int $agentId, string $title, string $description): void
    {
        $agent = Agent::find($agentId);
        if ($agent) {
            $agent->tasks()->create([
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'priority' => 'medium',
            ]);
            $this->loadAgents();
        }
    }

    protected function getViewData(): array
    {
        return [
            'agents' => $this->agents,
            'selectedAgent' => $this->selectedAgent,
            'viewMode' => $this->viewMode,
            'filterCategory' => $this->filterCategory,
            'filterStatus' => $this->filterStatus,
            'showCommunications' => $this->showCommunications,
            'systemOverview' => $this->systemOverview,
            'categories' => config('agency-agents.categories', []),
        ];
    }
}
