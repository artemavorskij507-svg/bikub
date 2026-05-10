<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\OfficeZone;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;

class AgencyAgentsOverviewWidget extends Widget
{
    protected static string $view = 'filament.widgets.agency-agents-overview';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 10;

    public $systemOverview = [];
    public $topPerformers = [];
    public $recentActivity = [];
    public $zoneStats = [];

    public function mount(): void
    {
        $service = app(AgentMonitoringService::class);
        $this->systemOverview = $service->getSystemOverview();
        $this->topPerformers = $service->getTopPerformers(5)->toArray();
        $this->loadRecentActivity();
        $this->loadZoneStats();
    }

    private function loadRecentActivity(): void
    {
        $this->recentActivity = AgentActivity::with('agent')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'agent_name' => $activity->agent->name,
                    'agent_emoji' => $activity->agent->emoji,
                    'activity_type' => $activity->activity_type,
                    'zone' => $activity->zone,
                    'description' => $activity->description,
                    'started_at' => $activity->started_at->diffForHumans(),
                ];
            })->toArray();
    }

    private function loadZoneStats(): void
    {
        $this->zoneStats = OfficeZone::all()->map(function ($zone) {
            return [
                'name' => $zone->name,
                'display_name' => $zone->display_name,
                'icon' => $zone->icon,
                'occupancy' => $zone->current_occupancy,
                'capacity' => $zone->capacity,
                'percentage' => $zone->getOccupancyPercentage(),
            ];
        })->toArray();
    }

    protected function getViewData(): array
    {
        return [
            'systemOverview' => $this->systemOverview,
            'topPerformers' => $this->topPerformers,
            'recentActivity' => $this->recentActivity,
            'zoneStats' => $this->zoneStats,
        ];
    }
}
