<?php

namespace App\Modules\AgencyAgents\Services;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentCommunication;
use App\Modules\AgencyAgents\Models\AgentMetric;
use App\Modules\AgencyAgents\Models\AgentTask;
use App\Modules\AgencyAgents\Models\OfficeZone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AgentMonitoringService
{
    private const CACHE_TTL = 300;

    public function getSystemOverview(): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return $this->emptyOverview();
        }

        return Cache::remember('agency_agents_overview', self::CACHE_TTL, function () {
            $totalAgents = Agent::count();
            $activeAgents = Agent::where('status', 'active')->count();
            $busyAgents = Agent::where('status', 'busy')->count();
            $idleAgents = Agent::where('status', 'idle')->count();

            $totalTasks = AgentTask::count();
            $completedTasks = AgentTask::where('status', 'completed')->count();
            $pendingTasks = AgentTask::where('status', 'pending')->count();
            $inProgressTasks = AgentTask::where('status', 'in_progress')->count();

            $avgPerformance = Agent::avg('performance_score') ?? 0;

            $zoneStats = Schema::hasTable('agency_office_zones')
                ? OfficeZone::all()->map(function ($zone) {
                    return [
                        'name' => $zone->name,
                        'display_name' => $zone->display_name,
                        'icon' => $zone->icon,
                        'occupancy' => $zone->current_occupancy,
                        'capacity' => $zone->capacity,
                        'percentage' => $zone->getOccupancyPercentage(),
                    ];
                })->toArray()
                : [];

            return [
                'status' => 'ready',
                'agents' => [
                    'total' => $totalAgents,
                    'active' => $activeAgents,
                    'busy' => $busyAgents,
                    'idle' => $idleAgents,
                    'offline' => $totalAgents - $activeAgents - $busyAgents - $idleAgents,
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'pending' => $pendingTasks,
                    'in_progress' => $inProgressTasks,
                    'failed' => AgentTask::where('status', 'failed')->count(),
                ],
                'performance' => [
                    'average_score' => round($avgPerformance, 2),
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                ],
                'communications' => [
                    'total_messages' => AgentCommunication::count(),
                    'unread_messages' => AgentCommunication::where('status', 'sent')->count(),
                ],
                'zones' => $zoneStats,
            ];
        });
    }

    public function getAgentPerformance(Agent $agent): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return [
                'status' => 'not_initialized',
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                ],
            ];
        }

        $tasks = $agent->tasks;
        $completedTasks = $tasks->where('status', 'completed');
        $failedTasks = $tasks->where('status', 'failed');

        $avgCompletionTime = $completedTasks->avg(function ($task) {
            if ($task->started_at && $task->completed_at) {
                return $task->started_at->diffInSeconds($task->completed_at);
            }

            return null;
        });

        return [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'category' => $agent->category,
                'status' => $agent->status,
                'current_zone' => $agent->current_zone,
                'position' => [
                    'x' => $agent->position_x,
                    'y' => $agent->position_y,
                ],
                'current_activity' => $agent->current_activity,
            ],
            'tasks' => [
                'total' => $tasks->count(),
                'completed' => $completedTasks->count(),
                'failed' => $failedTasks->count(),
                'success_rate' => $tasks->count() > 0 ? round(($completedTasks->count() / $tasks->count()) * 100, 2) : 0,
            ],
            'performance' => [
                'current_score' => $agent->performance_score,
                'avg_completion_time' => $avgCompletionTime ? round($avgCompletionTime, 2) : null,
                'tasks_completed' => $agent->tasks_completed,
            ],
            'communications' => [
                'sent' => AgentCommunication::where('sender_agent_id', $agent->id)->count(),
                'received' => AgentCommunication::where('receiver_agent_id', $agent->id)->count(),
            ],
            'recent_activity' => $this->getRecentActivity($agent),
        ];
    }

    public function getCategoryStats(): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return [];
        }

        return Cache::remember('agency_agents_category_stats', self::CACHE_TTL, function () {
            $categories = Agent::selectRaw('category, COUNT(*) as count, AVG(performance_score) as avg_score')
                ->groupBy('category')
                ->get();

            return $categories->map(function ($category) {
                $tasks = AgentTask::whereIn('agent_id', Agent::where('category', $category->category)->pluck('id'))->get();

                return [
                    'category' => $category->category,
                    'agent_count' => $category->count,
                    'avg_performance' => round($category->avg_score, 2),
                    'total_tasks' => $tasks->count(),
                    'completed_tasks' => $tasks->where('status', 'completed')->count(),
                ];
            })->toArray();
        });
    }

    public function getTopPerformers(int $limit = 10): EloquentCollection
    {
        if (! Schema::hasTable('agency_agents')) {
            return new EloquentCollection();
        }

        return Agent::where('status', '!=', 'offline')
            ->orderBy('performance_score', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentActivity(Agent $agent, int $days = 7): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return ['tasks' => [], 'communications' => []];
        }

        $since = Carbon::now()->subDays($days);

        $recentTasks = AgentTask::where('agent_id', $agent->id)
            ->where('updated_at', '>=', $since)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $recentCommunications = AgentCommunication::where('sender_agent_id', $agent->id)
            ->orWhere('receiver_agent_id', $agent->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'tasks' => $recentTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'updated_at' => $task->updated_at->toISOString(),
                ];
            })->toArray(),
            'communications' => $recentCommunications->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'type' => $comm->message_type,
                    'sender' => $comm->sender->name,
                    'receiver' => $comm->receiver->name,
                    'created_at' => $comm->created_at->toISOString(),
                ];
            })->toArray(),
        ];
    }

    public function getSystemHealth(): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return [
                'status' => 'not_initialized',
                'issues' => [[
                    'type' => 'warning',
                    'message' => 'Agency Agents tables are missing in the current database.',
                    'count' => 1,
                ]],
                'checked_at' => now()->toISOString(),
            ];
        }

        $issues = [];

        $lowPerformers = Agent::where('performance_score', '<', 50)
            ->where('status', '!=', 'offline')
            ->count();

        if ($lowPerformers > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$lowPerformers} agents with performance below 50%",
                'count' => $lowPerformers,
            ];
        }

        $stuckTasks = AgentTask::where('status', 'in_progress')
            ->where('updated_at', '<', Carbon::now()->subHours(24))
            ->count();

        if ($stuckTasks > 0) {
            $issues[] = [
                'type' => 'error',
                'message' => "{$stuckTasks} tasks stuck in progress for over 24 hours",
                'count' => $stuckTasks,
            ];
        }

        $unreadMessages = AgentCommunication::where('status', 'sent')
            ->where('created_at', '<', Carbon::now()->subHours(1))
            ->count();

        if ($unreadMessages > 10) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$unreadMessages} unread messages older than 1 hour",
                'count' => $unreadMessages,
            ];
        }

        if (Schema::hasTable('agency_office_zones')) {
            foreach (OfficeZone::all() as $zone) {
                if ($zone->getOccupancyPercentage() > 90) {
                    $issues[] = [
                        'type' => 'warning',
                        'message' => "Zone {$zone->display_name} is over 90% capacity",
                        'count' => 1,
                    ];
                }
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'issues_detected',
            'issues' => $issues,
            'checked_at' => now()->toISOString(),
        ];
    }

    public function generateReport(string $period = 'daily'): array
    {
        if (! $this->hasCoreAgencyTables()) {
            return [
                'status' => 'not_initialized',
                'period' => $period,
                'summary' => [
                    'tasks_created' => 0,
                    'tasks_completed' => 0,
                    'messages_sent' => 0,
                    'metrics_recorded' => 0,
                    'activities_logged' => 0,
                ],
            ];
        }

        $startDate = match ($period) {
            'daily' => Carbon::now()->startOfDay(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfDay(),
        };

        $tasks = AgentTask::where('created_at', '>=', $startDate)->get();
        $communications = AgentCommunication::where('created_at', '>=', $startDate)->get();
        $metrics = Schema::hasTable('agency_agent_metrics')
            ? AgentMetric::where('recorded_at', '>=', $startDate)->get()
            : collect();
        $activities = Schema::hasTable('agency_agent_activities')
            ? AgentActivity::where('started_at', '>=', $startDate)->get()
            : collect();

        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => now()->toISOString(),
            'summary' => [
                'tasks_created' => $tasks->count(),
                'tasks_completed' => $tasks->where('status', 'completed')->count(),
                'messages_sent' => $communications->count(),
                'metrics_recorded' => $metrics->count(),
                'activities_logged' => $activities->count(),
            ],
            'top_performers' => $this->getTopPerformers(5)->map(function ($agent) {
                return [
                    'name' => $agent->name,
                    'category' => $agent->category,
                    'score' => $agent->performance_score,
                ];
            })->toArray(),
            'category_breakdown' => $this->getCategoryStats(),
            'zone_breakdown' => Schema::hasTable('agency_office_zones')
                ? OfficeZone::all()->map(function ($zone) {
                    return [
                        'name' => $zone->display_name,
                        'occupancy' => $zone->current_occupancy,
                        'capacity' => $zone->capacity,
                    ];
                })->toArray()
                : [],
        ];
    }

    public function updateAgentStatus(Agent $agent, string $status): void
    {
        if (! Schema::hasTable('agency_agents')) {
            return;
        }

        $agent->update([
            'status' => $status,
            'last_active_at' => now(),
        ]);

        Cache::forget('agency_agents_overview');
        Cache::forget('agency_agents_category_stats');
    }

    public function recordMetric(Agent $agent, string $type, float $value, string $unit, array $context = []): AgentMetric
    {
        if (! Schema::hasTable('agency_agent_metrics')) {
            throw new RuntimeException('Agency agent metrics table is missing in the current database.');
        }

        return AgentMetric::create([
            'agent_id' => $agent->id,
            'metric_type' => $type,
            'value' => $value,
            'unit' => $unit,
            'context' => $context,
            'recorded_at' => now(),
        ]);
    }

    public function getZoneOccupancyStats(): array
    {
        if (! Schema::hasTable('agency_office_zones')) {
            return [];
        }

        return OfficeZone::all()->map(function ($zone) {
            return [
                'name' => $zone->name,
                'display_name' => $zone->display_name,
                'icon' => $zone->icon,
                'occupancy' => $zone->current_occupancy,
                'capacity' => $zone->capacity,
                'percentage' => $zone->getOccupancyPercentage(),
                'available_spots' => $zone->getAvailableSpots(),
            ];
        })->toArray();
    }

    public function getAgentDistributionByZone(): array
    {
        if (! $this->hasCoreAgencyTables() || ! Schema::hasTable('agency_office_zones')) {
            return [];
        }

        $distribution = [];

        foreach (OfficeZone::all() as $zone) {
            $agents = Agent::where('current_zone', $zone->name)->get();
            $distribution[$zone->name] = [
                'zone_name' => $zone->display_name,
                'total_agents' => $agents->count(),
                'by_status' => [
                    'active' => $agents->where('status', 'active')->count(),
                    'busy' => $agents->where('status', 'busy')->count(),
                    'idle' => $agents->where('status', 'idle')->count(),
                ],
                'by_category' => $agents->groupBy('category')->map->count(),
            ];
        }

        return $distribution;
    }

    protected function hasCoreAgencyTables(): bool
    {
        return Schema::hasTable('agency_agents')
            && Schema::hasTable('agency_agent_tasks')
            && Schema::hasTable('agency_agent_communications');
    }

    protected function emptyOverview(): array
    {
        return [
            'status' => 'not_initialized',
            'message' => 'Agency Agents tables are missing in the current database.',
            'agents' => [
                'total' => 0,
                'active' => 0,
                'busy' => 0,
                'idle' => 0,
                'offline' => 0,
            ],
            'tasks' => [
                'total' => 0,
                'completed' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'failed' => 0,
            ],
            'performance' => [
                'average_score' => 0,
                'completion_rate' => 0,
            ],
            'communications' => [
                'total_messages' => 0,
                'unread_messages' => 0,
            ],
            'zones' => [],
        ];
    }
}
