<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VirtualOffice\AgentController;
use App\Http\Controllers\Api\VirtualOffice\TaskController;
use App\Http\Controllers\Api\VirtualOffice\MessageController;
use App\Http\Controllers\Api\VirtualOffice\OfficeZoneController;
use App\Http\Controllers\Api\VirtualOffice\CategoryController;

/*
|--------------------------------------------------------------------------
| Virtual Office API Routes
|--------------------------------------------------------------------------
|
| Маршруты для API виртуального офиса с пиксельными агентами.
| Все маршруты требуют аутентификации через Sanctum.
|
*/

Route::prefix('virtual-office')->middleware(['auth:sanctum'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Агенты (Agents)
    |----------------------------------------------------------------------
    */
    Route::prefix('agents')->group(function () {
        // Получить список всех агентов
        Route::get('/', [AgentController::class, 'index']);

        // Получить статистику агентов
        Route::get('/stats', [AgentController::class, 'stats']);

        // Получить агента по ID
        Route::get('/{id}', [AgentController::class, 'show']);

        // Создать нового агента
        Route::post('/', [AgentController::class, 'store']);

        // Обновить агента
        Route::put('/{id}', [AgentController::class, 'update']);

        // Удалить агента
        Route::delete('/{id}', [AgentController::class, 'destroy']);

        // Переместить агента
        Route::post('/{id}/move', [AgentController::class, 'move']);

        // Переместить агента в зону
        Route::post('/{id}/move-to-zone', [AgentController::class, 'moveToZone']);

        // Получить агентов по категории
        Route::get('/by-category/{categoryId}', [AgentController::class, 'byCategory']);

        // Получить агентов по зоне
        Route::get('/by-zone/{zoneId}', [AgentController::class, 'byZone']);
    });

    /*
    |----------------------------------------------------------------------
    | Задачи (Tasks)
    |----------------------------------------------------------------------
    */
    Route::prefix('tasks')->group(function () {
        // Получить список всех задач
        Route::get('/', [TaskController::class, 'index']);

        // Получить статистику задач
        Route::get('/stats', [TaskController::class, 'stats']);

        // Получить просроченные задачи
        Route::get('/overdue', [TaskController::class, 'overdue']);

        // Получить задачу по ID
        Route::get('/{id}', [TaskController::class, 'show']);

        // Создать новую задачу
        Route::post('/', [TaskController::class, 'store']);

        // Обновить задачу
        Route::put('/{id}', [TaskController::class, 'update']);

        // Удалить задачу
        Route::delete('/{id}', [TaskController::class, 'destroy']);

        // Начать выполнение задачи
        Route::post('/{id}/start', [TaskController::class, 'start']);

        // Завершить задачу
        Route::post('/{id}/complete', [TaskController::class, 'complete']);

        // Отменить задачу
        Route::post('/{id}/cancel', [TaskController::class, 'cancel']);

        // Получить задачи агента
        Route::get('/by-agent/{agentId}', [TaskController::class, 'byAgent']);

        // Получить активные задачи агента
        Route::get('/active-by-agent/{agentId}', [TaskController::class, 'activeByAgent']);
    });

    /*
    |----------------------------------------------------------------------
    | Сообщения (Messages)
    |----------------------------------------------------------------------
    */
    Route::prefix('messages')->group(function () {
        // Получить список сообщений
        Route::get('/', [MessageController::class, 'index']);

        // Получить статистику сообщений
        Route::get('/stats', [MessageController::class, 'stats']);

        // Поиск сообщений
        Route::get('/search', [MessageController::class, 'search']);

        // Получить сообщение по ID
        Route::get('/{id}', [MessageController::class, 'show']);

        // Отправить сообщение агенту
        Route::post('/', [MessageController::class, 'store']);

        // Удалить сообщение
        Route::delete('/{id}', [MessageController::class, 'destroy']);

        // Получить историю чата с агентом
        Route::get('/chat-history/{agentId}', [MessageController::class, 'chatHistory']);

        // Получить последние сообщения агента
        Route::get('/recent-by-agent/{agentId}', [MessageController::class, 'recentByAgent']);
    });

    /*
    |----------------------------------------------------------------------
    | Офисные зоны (Office Zones)
    |----------------------------------------------------------------------
    */
    Route::prefix('office-zones')->group(function () {
        // Получить список всех зон
        Route::get('/', [OfficeZoneController::class, 'index']);

        // Получить статистику зон
        Route::get('/stats', [OfficeZoneController::class, 'stats']);

        // Получить зону по slug
        Route::get('/by-slug/{slug}', [OfficeZoneController::class, 'bySlug']);

        // Получить зону по ID
        Route::get('/{id}', [OfficeZoneController::class, 'show']);

        // Создать новую зону
        Route::post('/', [OfficeZoneController::class, 'store']);

        // Обновить зону
        Route::put('/{id}', [OfficeZoneController::class, 'update']);

        // Удалить зону
        Route::delete('/{id}', [OfficeZoneController::class, 'destroy']);

        // Получить агентов в зоне
        Route::get('/{id}/agents', [OfficeZoneController::class, 'agents']);

        // Получить активных агентов в зоне
        Route::get('/{id}/active-agents', [OfficeZoneController::class, 'activeAgents']);

        // Проверить, находится ли точка в зоне
        Route::post('/{id}/contains-point', [OfficeZoneController::class, 'containsPoint']);
    });

    /*
    |----------------------------------------------------------------------
    | Категории (Categories)
    |----------------------------------------------------------------------
    */
    Route::prefix('categories')->group(function () {
        // Получить список всех категорий
        Route::get('/', [CategoryController::class, 'index']);

        // Получить статистику категорий
        Route::get('/stats', [CategoryController::class, 'stats']);

        // Получить категорию по slug
        Route::get('/by-slug/{slug}', [CategoryController::class, 'bySlug']);

        // Получить категорию по ID
        Route::get('/{id}', [CategoryController::class, 'show']);

        // Создать новую категорию
        Route::post('/', [CategoryController::class, 'store']);

        // Обновить категорию
        Route::put('/{id}', [CategoryController::class, 'update']);

        // Удалить категорию
        Route::delete('/{id}', [CategoryController::class, 'destroy']);

        // Получить агентов категории
        Route::get('/{id}/agents', [CategoryController::class, 'agents']);

        // Получить активных агентов категории
        Route::get('/{id}/active-agents', [CategoryController::class, 'activeAgents']);

        // Проверить, находится ли точка в секторе категории
        Route::post('/{id}/contains-point', [CategoryController::class, 'containsPoint']);

        // Получить случайную позицию в секторе категории
        Route::get('/{id}/random-position', [CategoryController::class, 'randomPosition']);
    });

    /*
    |----------------------------------------------------------------------
    | Общая статистика офиса
    |----------------------------------------------------------------------
    */
    Route::get('/office-stats', function () {
        $agents = \App\Models\VirtualOffice\Agent::count();
        $activeAgents = \App\Models\VirtualOffice\Agent::where('is_active', true)->count();
        $tasks = \App\Models\VirtualOffice\Task::count();
        $pendingTasks = \App\Models\VirtualOffice\Task::where('status', 'pending')->count();
        $inProgressTasks = \App\Models\VirtualOffice\Task::where('status', 'in_progress')->count();
        $completedTasks = \App\Models\VirtualOffice\Task::where('status', 'completed')->count();
        $messages = \App\Models\VirtualOffice\Message::count();
        $zones = \App\Models\VirtualOffice\OfficeZone::count();
        $categories = \App\Models\VirtualOffice\Category::count();

        return response()->json([
            'success' => true,
            'data' => [
                'agents' => [
                    'total' => $agents,
                    'active' => $activeAgents,
                    'inactive' => $agents - $activeAgents,
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
                'office' => [
                    'zones' => $zones,
                    'categories' => $categories,
                ],
            ],
        ]);
    });
});
