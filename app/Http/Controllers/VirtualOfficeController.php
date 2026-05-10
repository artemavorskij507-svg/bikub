<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Message;

class VirtualOfficeController extends Controller
{
    /**
     * Главная страница виртуального офиса
     */
    public function index()
    {
        return view('virtual-office.index');
    }

    /**
     * Получить статистику офиса
     */
    public function stats()
    {
        $agents = Agent::count();
        $activeAgents = Agent::where('is_active', true)->count();
        $zones = OfficeZone::count();
        $categories = Category::count();
        $tasks = Task::count();
        $pendingTasks = Task::where('status', 'pending')->count();
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        $completedTasks = Task::where('status', 'completed')->count();
        $messages = Message::count();

        return response()->json([
            'success' => true,
            'data' => [
                'agents' => [
                    'total' => $agents,
                    'active' => $activeAgents,
                    'inactive' => $agents - $activeAgents,
                ],
                'office' => [
                    'zones' => $zones,
                    'categories' => $categories,
                ],
                'tasks' => [
                    'total' => $tasks,
                    'pending' => $pendingTasks,
                    'in_progress' => $inProgressTasks,
                    'completed' => $completedTasks,
                ],
                'messages' => [
                    'total' => $messages,
                ],
            ],
        ]);
    }

    /**
     * Получить всех агентов
     */
    public function agents(Request $request)
    {
        $query = Agent::with(['category', 'zone']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $agents = $query->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Получить агента по ID
     */
    public function agent($id)
    {
        $agent = Agent::with(['category', 'zone', 'tasks'])->find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }

    /**
     * Переместить агента
     */
    public function moveAgent(Request $request, $id)
    {
        $request->validate([
            'x_position' => 'required|integer|min:0|max:800',
            'y_position' => 'required|integer|min:0|max:600',
        ]);

        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $agent->update([
            'x_position' => $request->x_position,
            'y_position' => $request->y_position,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Агент перемещен',
            'data' => $agent,
        ]);
    }

    /**
     * Получить все зоны
     */
    public function zones()
    {
        $zones = OfficeZone::withCount(['agents', 'activeAgents'])->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Получить все категории
     */
    public function categories()
    {
        $categories = Category::withCount(['agents', 'activeAgents'])->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Получить все задачи
     */
    public function tasks(Request $request)
    {
        $query = Task::with(['agent']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Создать задачу
     */
    public function createTask(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'agent_id' => 'required|exists:agents,id',
            'priority' => 'required|in:low,medium,high,critical',
            'deadline' => 'nullable|date|after:now',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'agent_id' => $request->agent_id,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Задача создана',
            'data' => $task,
        ], 201);
    }

    /**
     * Получить сообщения агента
     */
    public function messages($agentId, Request $request)
    {
        $limit = $request->get('limit', 50);

        $messages = Message::where('agent_id', $agentId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Отправить сообщение
     */
    public function sendMessage(Request $request, $agentId)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        // Создать сообщение от пользователя
        $userMessage = Message::create([
            'agent_id' => $agentId,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'role' => 'user',
        ]);

        // TODO: Здесь будет логика AI-ответа агента
        // Пока создаем заглушку ответа
        $agentMessage = Message::create([
            'agent_id' => $agentId,
            'user_id' => auth()->id(),
            'content' => "Спасибо за сообщение! Я агент {$agent->name}. Как я могу помочь?",
            'role' => 'agent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено',
            'data' => [
                'user_message' => $userMessage,
                'agent_message' => $agentMessage,
            ],
        ], 201);
    }
}
