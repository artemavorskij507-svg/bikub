<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentService
{
    /**
     * Получить всех агентов с фильтрацией
     */
    public function getAgents(array $filters = []): Collection
    {
        $query = Agent::with(['category', 'zone']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->get();
    }

    /**
     * Получить агента по ID
     */
    public function getAgent(int $id): ?Agent
    {
        return Agent::with(['category', 'zone', 'tasks'])->find($id);
    }

    /**
     * Получить агента по slug
     */
    public function getAgentBySlug(string $slug): ?Agent
    {
        return Agent::with(['category', 'zone', 'tasks'])->where('slug', $slug)->first();
    }

    /**
     * Создать нового агента
     */
    public function createAgent(array $data): Agent
    {
        $agent = Agent::create($data);

        Log::info('Агент создан', [
            'agent_id' => $agent->id,
            'name' => $agent->name,
            'category_id' => $agent->category_id,
            'zone_id' => $agent->zone_id,
        ]);

        return $agent->load(['category', 'zone']);
    }

    /**
     * Обновить агента
     */
    public function updateAgent(int $id, array $data): ?Agent
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return null;
        }

        $agent->update($data);

        Log::info('Агент обновлен', [
            'agent_id' => $agent->id,
            'name' => $agent->name,
        ]);

        return $agent->load(['category', 'zone']);
    }

    /**
     * Удалить агента
     */
    public function deleteAgent(int $id): bool
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return false;
        }

        $agent->delete();

        Log::info('Агент удален', [
            'agent_id' => $id,
            'name' => $agent->name,
        ]);

        return true;
    }

    /**
     * Переместить агента
     */
    public function moveAgent(int $id, int $x, int $y): ?Agent
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return null;
        }

        // Проверить границы офиса
        if ($x < 0 || $x > 800 || $y < 0 || $y > 600) {
            Log::warning('Попытка переместить агента за пределы офиса', [
                'agent_id' => $id,
                'x' => $x,
                'y' => $y,
            ]);
            return null;
        }

        $oldX = $agent->x_position;
        $oldY = $agent->y_position;

        $agent->update([
            'x_position' => $x,
            'y_position' => $y,
        ]);

        Log::info('Агент перемещен', [
            'agent_id' => $id,
            'name' => $agent->name,
            'from' => ['x' => $oldX, 'y' => $oldY],
            'to' => ['x' => $x, 'y' => $y],
        ]);

        return $agent;
    }

    /**
     * Переместить агента в зону
     */
    public function moveAgentToZone(int $agentId, int $zoneId): ?Agent
    {
        $agent = Agent::find($agentId);
        $zone = OfficeZone::find($zoneId);

        if (!$agent || !$zone) {
            return null;
        }

        // Получить случайную позицию в зоне
        $x = rand($zone->x_min, $zone->x_max);
        $y = rand($zone->y_min, $zone->y_max);

        $agent->update([
            'zone_id' => $zoneId,
            'x_position' => $x,
            'y_position' => $y,
        ]);

        Log::info('Агент перемещен в зону', [
            'agent_id' => $agentId,
            'name' => $agent->name,
            'zone_id' => $zoneId,
            'zone_name' => $zone->name,
            'position' => ['x' => $x, 'y' => $y],
        ]);

        return $agent->load(['category', 'zone']);
    }

    /**
     * Активировать/деактивировать агента
     */
    public function toggleAgentStatus(int $id): ?Agent
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return null;
        }

        $agent->update([
            'is_active' => !$agent->is_active,
        ]);

        Log::info('Статус агента изменен', [
            'agent_id' => $id,
            'name' => $agent->name,
            'is_active' => $agent->is_active,
        ]);

        return $agent;
    }

    /**
     * Получить статистику агентов
     */
    public function getAgentStats(): array
    {
        $totalAgents = Agent::count();
        $activeAgents = Agent::where('is_active', true)->count();
        $inactiveAgents = $totalAgents - $activeAgents;

        $agentsByCategory = Category::withCount('agents')->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $category->agents_count,
            ];
        });

        $agentsByZone = OfficeZone::withCount('agents')->get()->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'count' => $zone->agents_count,
            ];
        });

        return [
            'total' => $totalAgents,
            'active' => $activeAgents,
            'inactive' => $inactiveAgents,
            'by_category' => $agentsByCategory,
            'by_zone' => $agentsByZone,
        ];
    }

    /**
     * Получить агентов в радиусе от точки
     */
    public function getAgentsNearPoint(int $x, int $y, int $radius = 50): Collection
    {
        return Agent::where('is_active', true)
            ->where(function ($query) use ($x, $y, $radius) {
                $query->whereBetween('x_position', [$x - $radius, $x + $radius])
                      ->whereBetween('y_position', [$y - $radius, $y + $radius]);
            })
            ->with(['category', 'zone'])
            ->get();
    }

    /**
     * Отправить сообщение агенту
     */
    public function sendMessageToAgent(int $agentId, string $content): array
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
        ]);

        // TODO: Здесь будет логика AI-ответа агента
        // Пока создаем заглушку ответа
        $agentMessage = Message::create([
            'agent_id' => $agentId,
            'user_id' => Auth::id(),
            'content' => "Спасибо за сообщение! Я агент {$agent->name}. Как я могу помочь?",
            'role' => 'agent',
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
     * Создать задачу для агента
     */
    public function createTaskForAgent(int $agentId, array $data): Task
    {
        $agent = Agent::find($agentId);

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'agent_id' => $agentId,
            'priority' => $data['priority'] ?? 'medium',
            'deadline' => $data['deadline'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Задача создана для агента', [
            'task_id' => $task->id,
            'agent_id' => $agentId,
            'agent_name' => $agent->name,
            'title' => $task->title,
            'priority' => $task->priority,
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
     * Импортировать агентов из agency-agents
     */
    public function importAgentsFromAgencyAgents(): array
    {
        $basePath = base_path('agency-agents');
        $imported = 0;
        $errors = [];

        $categories = [
            'academic', 'design', 'engineering', 'game-development',
            'marketing', 'paid-media', 'product', 'project-management',
            'sales', 'spatial-computing', 'specialized', 'support', 'testing'
        ];

        foreach ($categories as $categorySlug) {
            $categoryPath = $basePath . '/' . $categorySlug;

            if (!is_dir($categoryPath)) {
                continue;
            }

            // Создать или найти категорию
            $category = Category::firstOrCreate(
                ['slug' => $categorySlug],
                [
                    'name' => ucfirst(str_replace('-', ' ', $categorySlug)),
                    'description' => 'Категория: ' . ucfirst(str_replace('-', ' ', $categorySlug)),
                    'color' => $this->getCategoryColor($categorySlug),
                    'icon' => $this->getCategoryIcon($categorySlug),
                    'sector_x_min' => 0,
                    'sector_x_max' => 200,
                    'sector_y_min' => 0,
                    'sector_y_max' => 200,
                ]
            );

            // Найти все .md файлы в категории
            $files = glob($categoryPath . '/*.md');

            foreach ($files as $file) {
                try {
                    $content = file_get_contents($file);
                    $slug = pathinfo($file, PATHINFO_FILENAME);

                    // Извлечь имя из первой строки
                    $lines = explode("\n", $content);
                    $name = $lines[0] ?? $slug;
                    $name = str_replace('#', '', $name);
                    $name = trim($name);

                    // Извлечь описание
                    $description = '';
                    foreach ($lines as $line) {
                        if (strpos($line, '>') === 0) {
                            $description = trim(substr($line, 1));
                            break;
                        }
                    }

                    // Создать агента
                    Agent::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'description' => $description,
                            'category_id' => $category->id,
                            'zone_id' => OfficeZone::first()->id,
                            'x_position' => rand(50, 750),
                            'y_position' => rand(50, 550),
                            'emoji' => $this->getAgentEmoji($categorySlug),
                            'color' => $category->color,
                            'is_active' => true,
                            'source_file' => $file,
                        ]
                    );

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Ошибка импорта агента', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Получить цвет категории
     */
    private function getCategoryColor(string $category): string
    {
        $colors = [
            'academic' => '#3B82F6',
            'design' => '#EC4899',
            'engineering' => '#10B981',
            'game-development' => '#F59E0B',
            'marketing' => '#8B5CF6',
            'paid-media' => '#EF4444',
            'product' => '#06B6D4',
            'project-management' => '#F97316',
            'sales' => '#84CC16',
            'spatial-computing' => '#6366F1',
            'specialized' => '#14B8A6',
            'support' => '#F43F5E',
            'testing' => '#A855F7',
        ];

        return $colors[$category] ?? '#6B7280';
    }

    /**
     * Получить иконку категории
     */
    private function getCategoryIcon(string $category): string
    {
        $icons = [
            'academic' => '📚',
            'design' => '🎨',
            'engineering' => '⚙️',
            'game-development' => '🎮',
            'marketing' => '📢',
            'paid-media' => '💰',
            'product' => '📦',
            'project-management' => '📋',
            'sales' => '💼',
            'spatial-computing' => '🌐',
            'specialized' => '🔧',
            'support' => '🛟',
            'testing' => '🧪',
        ];

        return $icons[$category] ?? '📁';
    }

    /**
     * Получить эмодзи агента
     */
    private function getAgentEmoji(string $category): string
    {
        $emojis = [
            'academic' => '👨‍🎓',
            'design' => '👨‍🎨',
            'engineering' => '👨‍💻',
            'game-development' => '🎮',
            'marketing' => '📣',
            'paid-media' => '💵',
            'product' => '📦',
            'project-management' => '📊',
            'sales' => '💼',
            'spatial-computing' => '🌐',
            'specialized' => '🔧',
            'support' => '🛟',
            'testing' => '🧪',
        ];

        return $emojis[$category] ?? '👤';
    }
}
