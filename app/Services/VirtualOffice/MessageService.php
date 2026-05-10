<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\Message;
use App\Models\VirtualOffice\Agent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageService
{
    /**
     * Получить все сообщения с фильтрацией
     */
    public function getMessages(array $filters = []): Collection
    {
        $query = Message::with(['agent.category', 'user']);

        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить сообщение по ID
     */
    public function getMessage(int $id): ?Message
    {
        return Message::with(['agent.category', 'user'])->find($id);
    }

    /**
     * Отправить сообщение агенту
     */
    public function sendMessage(int $agentId, string $content, array $metadata = []): array
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            return [
                'success' => false,
                'message' => 'Агент не найден',
            ];
        }

        if (!$agent->is_active) {
            return [
                'success' => false,
                'message' => 'Агент не активен',
            ];
        }

        // Создать сообщение от пользователя
        $userMessage = Message::create([
            'agent_id' => $agentId,
            'user_id' => Auth::id(),
            'content' => $content,
            'role' => 'user',
            'metadata' => $metadata,
        ]);

        // TODO: Здесь будет логика AI-ответа агента
        // Пока создаем заглушку ответа
        $agentMessage = Message::create([
            'agent_id' => $agentId,
            'user_id' => Auth::id(),
            'content' => "Спасибо за сообщение! Я агент {$agent->name}. Как я могу помочь?",
            'role' => 'agent',
            'metadata' => ['type' => 'auto_reply'],
        ]);

        Log::info('Сообщение отправлено агенту', [
            'agent_id' => $agentId,
            'agent_name' => $agent->name,
            'user_id' => Auth::id(),
            'message_length' => strlen($content),
        ]);

        return [
            'success' => true,
            'user_message' => $userMessage,
            'agent_message' => $agentMessage,
        ];
    }

    /**
     * Удалить сообщение
     */
    public function deleteMessage(int $id): bool
    {
        $message = Message::find($id);

        if (!$message) {
            return false;
        }

        // Проверить, что пользователь может удалить сообщение
        if ($message->user_id !== Auth::id()) {
            Log::warning('Попытка удалить чужое сообщение', [
                'message_id' => $id,
                'user_id' => Auth::id(),
                'message_user_id' => $message->user_id,
            ]);
            return false;
        }

        $message->delete();

        Log::info('Сообщение удалено', [
            'message_id' => $id,
            'user_id' => Auth::id(),
        ]);

        return true;
    }

    /**
     * Получить историю чата с агентом
     */
    public function getChatHistory(int $agentId, int $limit = 50): Collection
    {
        return Message::where('agent_id', $agentId)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить последние сообщения агента
     */
    public function getRecentMessages(int $agentId, int $limit = 20): Collection
    {
        return Message::where('agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Поиск сообщений
     */
    public function searchMessages(string $query, ?int $agentId = null): Collection
    {
        $queryBuilder = Message::with(['agent.category', 'user'])
            ->where('content', 'like', '%' . $query . '%');

        if ($agentId) {
            $queryBuilder->where('agent_id', $agentId);
        }

        return $queryBuilder->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Получить статистику сообщений
     */
    public function getMessageStats(): array
    {
        $totalMessages = Message::count();
        $userMessages = Message::where('role', 'user')->count();
        $agentMessages = Message::where('role', 'agent')->count();

        $messagesByAgent = Agent::withCount('messages')->get()->map(function ($agent) {
            return [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'message_count' => $agent->messages_count,
            ];
        });

        $messagesByUser = \App\Models\User::withCount('messages')->get()->map(function ($user) {
            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'message_count' => $user->messages_count,
            ];
        });

        return [
            'total' => $totalMessages,
            'user_messages' => $userMessages,
            'agent_messages' => $agentMessages,
            'by_agent' => $messagesByAgent,
            'by_user' => $messagesByUser,
        ];
    }

    /**
     * Получить сообщения пользователя
     */
    public function getUserMessages(int $userId, array $filters = []): Collection
    {
        $query = Message::with(['agent.category'])
            ->where('user_id', $userId);

        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить последние сообщения пользователя
     */
    public function getUserRecentMessages(int $userId, int $limit = 50): Collection
    {
        return Message::with(['agent.category'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить сообщения агента
     */
    public function getAgentMessages(int $agentId, array $filters = []): Collection
    {
        $query = Message::with(['user'])
            ->where('agent_id', $agentId);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить количество непрочитанных сообщений
     */
    public function getUnreadCount(int $agentId, int $userId): int
    {
        // TODO: Реализовать логику прочтения сообщений
        // Пока возвращаем 0
        return 0;
    }

    /**
     * Отметить сообщения как прочитанные
     */
    public function markAsRead(int $agentId, int $userId): bool
    {
        // TODO: Реализовать логику прочтения сообщений
        // Пока возвращаем true
        return true;
    }

    /**
     * Получить топ агентов по количеству сообщений
     */
    public function getTopAgentsByMessages(int $limit = 10): Collection
    {
        return Agent::withCount('messages')
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($agent) {
                return [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'message_count' => $agent->messages_count,
                ];
            });
    }

    /**
     * Получить топ пользователей по количеству сообщений
     */
    public function getTopUsersByMessages(int $limit = 10): Collection
    {
        return \App\Models\User::withCount('messages')
            ->orderBy('messages_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'message_count' => $user->messages_count,
                ];
            });
    }

    /**
     * Получить среднюю длину сообщений
     */
    public function getAverageMessageLength(): float
    {
        $messages = Message::all();

        if ($messages->isEmpty()) {
            return 0;
        }

        $totalLength = $messages->sum(function ($message) {
            return strlen($message->content);
        });

        return round($totalLength / $messages->count(), 2);
    }

    /**
     * Получить сообщения по дате
     */
    public function getMessagesByDate(string $date): Collection
    {
        return Message::with(['agent.category', 'user'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Получить сообщения за период
     */
    public function getMessagesByDateRange(string $startDate, string $endDate): Collection
    {
        return Message::with(['agent.category', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
