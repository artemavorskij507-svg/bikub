<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Agent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Получить все задачи с фильтрацией
     */
    public function getTasks(array $filters = []): Collection
    {
        $query = Task::with(['agent.category', 'agent.zone']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('deadline', '<', now())
                  ->whereNot('status', 'completed');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить задачу по ID
     */
    public function getTask(int $id): ?Task
    {
        return Task::with(['agent.category', 'agent.zone'])->find($id);
    }

    /**
     * Создать новую задачу
     */
    public function createTask(array $data): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'agent_id' => $data['agent_id'],
            'priority' => $data['priority'] ?? 'medium',
            'deadline' => $data['deadline'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Задача создана', [
            'task_id' => $task->id,
            'title' => $task->title,
            'agent_id' => $task->agent_id,
            'priority' => $task->priority,
            'user_id' => Auth::id(),
        ]);

        return $task->load(['agent.category', 'agent.zone']);
    }

    /**
     * Обновить задачу
     */
    public function updateTask(int $id, array $data): ?Task
    {
        $task = Task::find($id);

        if (!$task) {
            return null;
        }

        $task->update($data);

        Log::info('Задача обновлена', [
            'task_id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
        ]);

        return $task->load(['agent.category', 'agent.zone']);
    }

    /**
     * Удалить задачу
     */
    public function deleteTask(int $id): bool
    {
        $task = Task::find($id);

        if (!$task) {
            return false;
        }

        $task->delete();

        Log::info('Задача удалена', [
            'task_id' => $id,
            'title' => $task->title,
        ]);

        return true;
    }

    /**
     * Начать выполнение задачи
     */
    public function startTask(int $id): ?Task
    {
        $task = Task::find($id);

        if (!$task) {
            return null;
        }

        if ($task->status !== 'pending') {
            Log::warning('Попытка начать задачу с неверным статусом', [
                'task_id' => $id,
                'current_status' => $task->status,
            ]);
            return null;
        }

        $task->update([
            'status' => 'in_progress',
        ]);

        Log::info('Задача начата', [
            'task_id' => $task->id,
            'title' => $task->title,
            'agent_id' => $task->agent_id,
        ]);

        return $task;
    }

    /**
     * Завершить задачу
     */
    public function completeTask(int $id): ?Task
    {
        $task = Task::find($id);

        if (!$task) {
            return null;
        }

        if ($task->status === 'completed') {
            Log::warning('Попытка завершить уже завершенную задачу', [
                'task_id' => $id,
            ]);
            return null;
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Задача завершена', [
            'task_id' => $task->id,
            'title' => $task->title,
            'agent_id' => $task->agent_id,
            'completed_at' => $task->completed_at,
        ]);

        return $task;
    }

    /**
     * Отменить задачу
     */
    public function cancelTask(int $id): ?Task
    {
        $task = Task::find($id);

        if (!$task) {
            return null;
        }

        if ($task->status === 'cancelled') {
            Log::warning('Попытка отменить уже отмененную задачу', [
                'task_id' => $id,
            ]);
            return null;
        }

        $task->update([
            'status' => 'cancelled',
        ]);

        Log::info('Задача отменена', [
            'task_id' => $task->id,
            'title' => $task->title,
            'agent_id' => $task->agent_id,
        ]);

        return $task;
    }

    /**
     * Получить задачи агента
     */
    public function getAgentTasks(int $agentId, array $filters = []): Collection
    {
        $query = Task::where('agent_id', $agentId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить активные задачи агента
     */
    public function getAgentActiveTasks(int $agentId): Collection
    {
        return Task::where('agent_id', $agentId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Получить просроченные задачи
     */
    public function getOverdueTasks(): Collection
    {
        return Task::with(['agent.category', 'agent.zone'])
            ->where('deadline', '<', now())
            ->whereNot('status', 'completed')
            ->orderBy('deadline', 'asc')
            ->get();
    }

    /**
     * Получить статистику задач
     */
    public function getTaskStats(): array
    {
        $totalTasks = Task::count();
        $pendingTasks = Task::where('status', 'pending')->count();
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        $completedTasks = Task::where('status', 'completed')->count();
        $cancelledTasks = Task::where('status', 'cancelled')->count();
        $overdueTasks = Task::where('deadline', '<', now())
            ->whereNot('status', 'completed')
            ->count();

        $tasksByPriority = [
            'critical' => Task::where('priority', 'critical')->count(),
            'high' => Task::where('priority', 'high')->count(),
            'medium' => Task::where('priority', 'medium')->count(),
            'low' => Task::where('priority', 'low')->count(),
        ];

        $tasksByAgent = Agent::withCount('tasks')->get()->map(function ($agent) {
            return [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'task_count' => $agent->tasks_count,
            ];
        });

        return [
            'total' => $totalTasks,
            'pending' => $pendingTasks,
            'in_progress' => $inProgressTasks,
            'completed' => $completedTasks,
            'cancelled' => $cancelledTasks,
            'overdue' => $overdueTasks,
            'by_priority' => $tasksByPriority,
            'by_agent' => $tasksByAgent,
        ];
    }

    /**
     * Получить задачи по приоритету
     */
    public function getTasksByPriority(string $priority): Collection
    {
        return Task::with(['agent.category', 'agent.zone'])
            ->where('priority', $priority)
            ->whereNot('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить задачи по статусу
     */
    public function getTasksByStatus(string $status): Collection
    {
        return Task::with(['agent.category', 'agent.zone'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Назначить задачу агенту
     */
    public function assignTaskToAgent(int $taskId, int $agentId): ?Task
    {
        $task = Task::find($taskId);
        $agent = Agent::find($agentId);

        if (!$task || !$agent) {
            return null;
        }

        $task->update([
            'agent_id' => $agentId,
        ]);

        Log::info('Задача назначена агенту', [
            'task_id' => $taskId,
            'task_title' => $task->title,
            'agent_id' => $agentId,
            'agent_name' => $agent->name,
        ]);

        return $task->load(['agent.category', 'agent.zone']);
    }

    /**
     * Изменить приоритет задачи
     */
    public function changeTaskPriority(int $taskId, string $priority): ?Task
    {
        $task = Task::find($taskId);

        if (!$task) {
            return null;
        }

        $oldPriority = $task->priority;

        $task->update([
            'priority' => $priority,
        ]);

        Log::info('Приоритет задачи изменен', [
            'task_id' => $taskId,
            'task_title' => $task->title,
            'old_priority' => $oldPriority,
            'new_priority' => $priority,
        ]);

        return $task;
    }

    /**
     * Установить дедлайн задачи
     */
    public function setTaskDeadline(int $taskId, $deadline): ?Task
    {
        $task = Task::find($taskId);

        if (!$task) {
            return null;
        }

        $task->update([
            'deadline' => $deadline,
        ]);

        Log::info('Дедлайн задачи установлен', [
            'task_id' => $taskId,
            'task_title' => $task->title,
            'deadline' => $deadline,
        ]);

        return $task;
    }
}
