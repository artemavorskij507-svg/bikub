<?php

namespace App\Modules\AgencyAgents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentCommunication;
use App\Modules\AgencyAgents\Models\OfficeZone;
use App\Modules\AgencyAgents\Services\AgentCommunicationService;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasAgencyAgentsTable()) {
            return $this->notInitializedResponse(['data' => [], 'total' => 0]);
        }

        $query = Agent::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('zone')) {
            $query->where('current_zone', $request->zone);
        }

        $agents = $query->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
            'total' => $agents->count(),
        ]);
    }

    public function show(Agent $agent): JsonResponse
    {
        $agent->load(['tasks', 'communications', 'metrics']);

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }

    public function updatePosition(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'x' => 'required|numeric|min:0|max:800',
            'y' => 'required|numeric|min:0|max:600',
        ]);

        $agent->updatePosition($validated['x'], $validated['y']);

        return response()->json([
            'success' => true,
            'message' => 'Position updated',
            'data' => [
                'x' => $agent->position_x,
                'y' => $agent->position_y,
                'zone' => $agent->current_zone,
            ],
        ]);
    }

    public function updateStatus(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,idle,busy,offline',
            'activity' => 'nullable|string',
            'message' => 'nullable|string|max:255',
        ]);

        $agent->updateStatus(
            $validated['status'],
            $validated['activity'] ?? null,
            $validated['message'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Status updated',
            'data' => [
                'status' => $agent->status,
                'activity' => $agent->current_activity,
                'message' => $agent->status_message,
            ],
        ]);
    }

    public function moveToZone(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'zone' => 'required|string|exists:agency_office_zones,name',
        ]);

        $agent->moveToZone($validated['zone']);

        return response()->json([
            'success' => true,
            'message' => 'Agent moving to zone',
            'data' => [
                'target_zone' => $agent->target_zone,
                'is_moving' => $agent->is_moving,
            ],
        ]);
    }

    public function tasks(Agent $agent): JsonResponse
    {
        $tasks = $agent->tasks()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    public function createTask(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:low,medium,high,critical',
            'deadline' => 'nullable|date|after:now',
            'target_zone' => 'nullable|string|exists:agency_office_zones,name',
        ]);

        $task = $agent->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'deadline' => $validated['deadline'] ?? null,
            'target_zone' => $validated['target_zone'] ?? null,
            'status' => 'pending',
        ]);

        AgentActivity::logTaskStart($agent, $task);

        return response()->json([
            'success' => true,
            'message' => 'Task created',
            'data' => $task,
        ], 201);
    }

    public function communications(Agent $agent): JsonResponse
    {
        $communications = AgentCommunication::where('sender_agent_id', $agent->id)
            ->orWhere('receiver_agent_id', $agent->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $communications,
        ]);
    }

    public function sendMessage(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:agency_agents,id',
            'content' => 'required|string|max:5000',
            'type' => 'in:message,assistance_request,knowledge_share,task_assignment',
            'priority' => 'in:low,normal,high',
        ]);

        $receiver = Agent::find($validated['receiver_id']);
        $service = app(AgentCommunicationService::class);

        $communication = $service->sendMessage(
            $agent,
            $receiver,
            $validated['content'],
            $validated['type'] ?? 'message',
            $validated['priority'] ?? 'normal'
        );

        AgentActivity::logCommunication($agent, $receiver, $validated['type'] ?? 'message');

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'data' => $communication,
        ], 201);
    }

    public function performance(Agent $agent): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $performance = $service->getAgentPerformance($agent);

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    public function metrics(Agent $agent, Request $request): JsonResponse
    {
        $query = $agent->metrics();

        if ($request->has('type')) {
            $query->where('metric_type', $request->type);
        }

        if ($request->has('days')) {
            $query->where('recorded_at', '>=', now()->subDays($request->days));
        }

        $metrics = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    public function activities(Agent $agent, Request $request): JsonResponse
    {
        $query = AgentActivity::where('agent_id', $agent->id);

        if ($request->has('days')) {
            $query->where('started_at', '>=', now()->subDays($request->days));
        }

        $activities = $query->latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    public function zones(): JsonResponse
    {
        if (! Schema::hasTable('agency_office_zones')) {
            return $this->notInitializedResponse(['data' => []]);
        }

        $zones = OfficeZone::all();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    public function zoneDetails(string $zoneName): JsonResponse
    {
        if (! $this->hasAgencyAgentsTable() || ! Schema::hasTable('agency_office_zones')) {
            return $this->notInitializedResponse([
                'data' => [
                    'zone' => null,
                    'agents' => [],
                    'occupancy' => 0,
                ],
            ]);
        }

        $zone = OfficeZone::where('name', $zoneName)->firstOrFail();
        $agents = Agent::where('current_zone', $zoneName)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $zone,
                'agents' => $agents,
                'occupancy' => $zone->getOccupancyPercentage(),
            ],
        ]);
    }

    public function zoneStats(): JsonResponse
    {
        if (! Schema::hasTable('agency_office_zones')) {
            return $this->notInitializedResponse(['data' => []]);
        }

        $zones = OfficeZone::all()->map(function ($zone) {
            return [
                'name' => $zone->name,
                'display_name' => $zone->display_name,
                'icon' => $zone->icon,
                'occupancy' => $zone->current_occupancy,
                'capacity' => $zone->capacity,
                'percentage' => $zone->getOccupancyPercentage(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    public function systemOverview(): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $overview = $service->getSystemOverview();

        if (Schema::hasTable('agency_office_zones')) {
            $overview['zones'] = OfficeZone::all()->map(function ($zone) {
                return [
                    'name' => $zone->name,
                    'occupancy' => $zone->current_occupancy,
                    'capacity' => $zone->capacity,
                ];
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    public function categoryStats(): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $stats = $service->getCategoryStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function topPerformers(Request $request): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $limit = $request->input('limit', 10);
        $performers = $service->getTopPerformers($limit);

        return response()->json([
            'success' => true,
            'data' => $performers,
        ]);
    }

    public function healthCheck(): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $health = $service->getSystemHealth();

        return response()->json([
            'success' => true,
            'data' => $health,
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $service = app(AgentMonitoringService::class);
        $period = $request->input('period', 'daily');
        $report = $service->generateReport($period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function recentActivities(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agency_agent_activities') || ! $this->hasAgencyAgentsTable()) {
            return $this->notInitializedResponse(['data' => []]);
        }

        $limit = $request->input('limit', 50);
        $activities = AgentActivity::with('agent')
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    public function heatmapData(): JsonResponse
    {
        if (! $this->hasAgencyAgentsTable()) {
            return $this->notInitializedResponse(['data' => []]);
        }

        $agents = Agent::select('position_x', 'position_y', 'status', 'category')->get();

        $heatmapData = $agents->map(function ($agent) {
            return [
                'x' => $agent->position_x,
                'y' => $agent->position_y,
                'value' => $agent->status === 'active' ? 2 : 1,
                'category' => $agent->category,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $heatmapData,
        ]);
    }

    protected function hasAgencyAgentsTable(): bool
    {
        return Schema::hasTable('agency_agents');
    }

    protected function notInitializedResponse(array $payload = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'status' => 'not_initialized',
            'message' => 'Agency Agents tables are missing in the current database.',
        ], $payload));
    }
}
