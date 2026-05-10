<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentCommunication;
use App\Modules\AgencyAgents\Services\AgentCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AgencyHubController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'app_url' => rtrim((string) config('app.url'), '/'),
                'chief_agent_slug' => config('agency-agents.chief_agent_slug', 'director-agent'),
                'multi_delegate_count' => (int) config('agency-agents.multi_delegate_count', 6),
            ],
        ]);
    }

    public function agents(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agency_agents')) {
            return response()->json(['success' => true, 'data' => [], 'total' => 0]);
        }

        $limit = min(500, max(1, (int) $request->query('limit', 100)));

        $query = Agent::query()->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $agents = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
            'total' => $agents->count(),
        ]);
    }

    public function communications(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agency_agent_communications')) {
            return response()->json(['success' => true, 'data' => [], 'total' => 0]);
        }

        $limit = min(200, max(1, (int) $request->query('limit', 50)));

        $q = AgentCommunication::query()->latest('id');

        if ($request->filled('agent_slug')) {
            $slug = (string) $request->query('agent_slug');
            $ids = Agent::query()->where('slug', $slug)->pluck('id');
            if ($ids->isEmpty()) {
                return response()->json(['success' => true, 'data' => [], 'total' => 0]);
            }
            $aid = (int) $ids->first();
            $q->where(function ($w) use ($aid): void {
                $w->where('sender_agent_id', $aid)->orWhere('receiver_agent_id', $aid);
            });
        }

        $rows = $q->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'total' => $rows->count(),
        ]);
    }

    public function storeCommunication(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agency_agents') || ! Schema::hasTable('agency_agent_communications')) {
            return response()->json([
                'success' => false,
                'message' => 'Agency tables are missing.',
            ], 503);
        }

        $validated = $request->validate([
            'sender_slug' => 'required|string|max:191',
            'receiver_slug' => 'required|string|max:191',
            'content' => 'required|string|max:20000',
            'type' => 'nullable|in:message,assistance_request,knowledge_share,task_assignment',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        $sender = Agent::query()->where('slug', $validated['sender_slug'])->first();
        $receiver = Agent::query()->where('slug', $validated['receiver_slug'])->first();

        if (! $sender || ! $receiver) {
            return response()->json([
                'success' => false,
                'message' => 'sender_slug or receiver_slug not found.',
            ], 422);
        }

        $comm = app(AgentCommunicationService::class)->sendMessage(
            $sender,
            $receiver,
            $validated['content'],
            $validated['type'] ?? 'message',
            $validated['priority'] ?? 'normal'
        );

        return response()->json([
            'success' => true,
            'data' => $comm,
        ], 201);
    }
}
