<?php

namespace App\Http\Controllers\Api\VirtualOffice;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Получить список всех задач
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['agent.category', 'agent.zone']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по приоритету
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Фильтрация по агенту
        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Фильтрация по дедлайну
        if ($request->has('overdue') && $request->boolean('overdue')) {
            $query->where('deadline', '<', now())
                ->whereNot('status', Task::STATUS_COMPLETED);
        }

        // Пагинация
        $perPage = $request->get('per_page', 50);
        $tasks = $query->orderByPriority()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Получить задачу по ID
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with(['agent.category', 'agent.zone'])->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * Создать новую задачу
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'agent_id' => 'required|exists:agents,id',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,critical',
            'deadline' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $task = Task::create($request->all());
        $task->load(['agent.category', 'agent.zone']);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => $task,
        ], 201);
    }

    /**
     * Обновить задачу
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:500',
            'description' => 'nullable|string',
            'agent_id' => 'sometimes|required|exists:agents,id',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|required|in:low,medium,high,critical',
            'deadline' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $task->update($request->all());
        $task->load(['agent.category', 'agent.zone']);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно обновлена',
            'data' => $task,
        ]);
    }

    /**
     * Удалить задачу
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена',
        ]);
    }

    /**
     * Начать выполнение задачи
     */
    public function start(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        if ($task->status !== Task::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Задача уже начата или завершена',
            ], 422);
        }

        $task->start();

        return response()->json([
            'success' => true,
            'message' => 'Задача начата',
            'data' => $task,
        ]);
    }

    /**
     * Завершить задачу
     */
    public function complete(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        if ($task->status === Task::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Задача уже завершена',
            ], 422);
        }

        $task->complete();

        return response()->json([
            'success' => true,
            'message' => 'Задача завершена',
            'data' => $task,
        ]);
    }

    /**
     * Отменить задачу
     */
    public function cancel(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена',
            ], 404);
        }

        if ($task->status === Task::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Задача уже отменена',
            ], 422);
        }

        $task->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Задача отменена',
            'data' => $task,
        ]);
    }

    /**
     * Получить задачи агента
     */
    public function byAgent(int $agentId): JsonResponse
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $tasks = Task::with(['agent.category', 'agent.zone'])
            ->where('agent_id', $agentId)
            ->orderByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Получить активные задачи агента
     */
    public function activeByAgent(int $agentId): JsonResponse
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $tasks = Task::with(['agent.category', 'agent.zone'])
            ->where('agent_id', $agentId)
            ->active()
            ->orderByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Получить просроченные задачи
     */
    public function overdue(): JsonResponse
    {
        $tasks = Task::with(['agent.category', 'agent.zone'])
            ->overdue()
            ->orderByPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Получить статистику задач
     */
    public function stats(): JsonResponse
    {
        $totalTasks = Task::count();
        $pendingTasks = Task::where('status', Task::STATUS_PENDING)->count();
        $inProgressTasks = Task::where('status', Task::STATUS_IN_PROGRESS)->count();
        $completedTasks = Task::where('status', Task::STATUS_COMPLETED)->count();
        $cancelledTasks = Task::where('status', Task::STATUS_CANCELLED)->count();
        $overdueTasks = Task::overdue()->count();

        $tasksByPriority = [
            'critical' => Task::where('priority', Task::PRIORITY_CRITICAL)->count(),
            'high' => Task::where('priority', Task::PRIORITY_HIGH)->count(),
            'medium' => Task::where('priority', Task::PRIORITY_MEDIUM)->count(),
            'low' => Task::where('priority', Task::PRIORITY_LOW)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalTasks,
                'pending' => $pendingTasks,
                'in_progress' => $inProgressTasks,
                'completed' => $completedTasks,
                'cancelled' => $cancelledTasks,
                'overdue' => $overdueTasks,
                'by_priority' => $tasksByPriority,
            ],
        ]);
    }
}
