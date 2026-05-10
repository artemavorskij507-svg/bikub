<?php

namespace App\Http\Controllers\Api\VirtualOffice;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice\Message;
use App\Models\VirtualOffice\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Получить список сообщений
     */
    public function index(Request $request): JsonResponse
    {
        $query = Message::with(['agent.category', 'user']);

        // Фильтрация по агенту
        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Фильтрация по пользователю
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Фильтрация по роли
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Пагинация
        $perPage = $request->get('per_page', 50);
        $messages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Получить сообщение по ID
     */
    public function show(int $id): JsonResponse
    {
        $message = Message::with(['agent.category', 'user'])->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Сообщение не найдено',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Отправить сообщение агенту
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|exists:agents,id',
            'content' => 'required|string|max:5000',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agent = Agent::find($request->agent_id);

        if (!$agent->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не активен',
            ], 422);
        }

        // Создать сообщение от пользователя
        $userMessage = Message::create([
            'agent_id' => $request->agent_id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'role' => Message::ROLE_USER,
            'metadata' => $request->metadata,
        ]);

        // TODO: Здесь будет логика AI-ответа агента
        // Пока создаем заглушку ответа
        $agentMessage = Message::create([
            'agent_id' => $request->agent_id,
            'user_id' => Auth::id(),
            'content' => "Спасибо за сообщение! Я агент {$agent->name}. Как я могу помочь?",
            'role' => Message::ROLE_AGENT,
            'metadata' => ['type' => 'auto_reply'],
        ]);

        $userMessage->load(['agent.category', 'user']);
        $agentMessage->load(['agent.category', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено',
            'data' => [
                'user_message' => $userMessage,
                'agent_reply' => $agentMessage,
            ],
        ], 201);
    }

    /**
     * Удалить сообщение
     */
    public function destroy(int $id): JsonResponse
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Сообщение не найдено',
            ], 404);
        }

        // Проверить, что пользователь может удалить сообщение
        if ($message->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для удаления этого сообщения',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено',
        ]);
    }

    /**
     * Получить историю чата с агентом
     */
    public function chatHistory(int $agentId, Request $request): JsonResponse
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $limit = $request->get('limit', 50);
        $messages = Message::with(['user'])
            ->where('agent_id', $agentId)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Получить последние сообщения агента
     */
    public function recentByAgent(int $agentId, Request $request): JsonResponse
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $limit = $request->get('limit', 20);
        $messages = Message::with(['user'])
            ->where('agent_id', $agentId)
            ->recent($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Поиск сообщений
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'agent_id' => 'nullable|exists:agents,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Message::with(['agent.category', 'user'])
            ->search($request->query);

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        $messages = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Получить статистику сообщений
     */
    public function stats(): JsonResponse
    {
        $totalMessages = Message::count();
        $userMessages = Message::fromUser()->count();
        $agentMessages = Message::fromAgent()->count();

        $messagesByAgent = Agent::withCount('messages')->get()->map(function ($agent) {
            return [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'message_count' => $agent->messages_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalMessages,
                'user_messages' => $userMessages,
                'agent_messages' => $agentMessages,
                'by_agent' => $messagesByAgent,
            ],
        ]);
    }
}
