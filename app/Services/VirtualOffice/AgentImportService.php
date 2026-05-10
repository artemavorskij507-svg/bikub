<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AgentImportService
{
    /**
     * Импортировать агентов из директории agency-agents
     */
    public function importFromDirectory(string $basePath = null): array
    {
        $basePath = $basePath ?? base_path('agency-agents');
        $imported = 0;
        $errors = [];
        $skipped = 0;

        if (!File::isDirectory($basePath)) {
            Log::error('Директория agency-agents не найдена', ['path' => $basePath]);
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Директория agency-agents не найдена'],
            ];
        }

        $categories = File::directories($basePath);

        foreach ($categories as $categoryPath) {
            $categorySlug = basename($categoryPath);

            // Пропустить служебные директории
            if (in_array($categorySlug, ['scripts', 'integrations', 'examples', '.github'])) {
                continue;
            }

            try {
                // Создать или найти категорию
                $category = $this->getOrCreateCategory($categorySlug);

                // Найти все .md файлы в категории
                $files = File::glob($categoryPath . '/*.md');

                foreach ($files as $file) {
                    try {
                        $result = $this->importAgentFromFile($file, $category);

                        if ($result['success']) {
                            $imported++;
                        } else {
                            $skipped++;
                            if (isset($result['error'])) {
                                $errors[] = $result['error'];
                            }
                        }
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
            } catch (\Exception $e) {
                $errors[] = [
                    'category' => $categorySlug,
                    'error' => $e->getMessage(),
                ];
                Log::error('Ошибка обработки категории', [
                    'category' => $categorySlug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Импорт агентов завершен', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => count($errors),
        ]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Импортировать агента из файла
     */
    private function importAgentFromFile(string $filePath, Category $category): array
    {
        $content = File::get($filePath);
        $slug = basename($filePath, '.md');

        // Проверить, существует ли уже агент
        $existingAgent = Agent::where('slug', $slug)->first();

        if ($existingAgent) {
            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'Агент уже существует',
            ];
        }

        // Извлечь данные из файла
        $agentData = $this->parseAgentFile($content, $slug);

        if (!$agentData) {
            return [
                'success' => false,
                'error' => [
                    'file' => $filePath,
                    'error' => 'Не удалось распарсить файл',
                ],
            ];
        }

        // Получить случайную зону
        $zone = OfficeZone::inRandomOrder()->first();

        if (!$zone) {
            $zone = $this->createDefaultZone();
        }

        // Получить случайную позицию в секторе категории
        $position = $category->getRandomPosition();

        // Создать агента
        $agent = Agent::create([
            'name' => $agentData['name'],
            'slug' => $slug,
            'description' => $agentData['description'],
            'category_id' => $category->id,
            'zone_id' => $zone->id,
            'x_position' => $position['x'],
            'y_position' => $position['y'],
            'emoji' => $agentData['emoji'] ?? $this->getAgentEmoji($category->slug),
            'color' => $category->color,
            'is_active' => true,
            'source_file' => $filePath,
            'config' => [
                'personality' => $agentData['personality'] ?? 'friendly',
                'expertise' => $agentData['expertise'] ?? [],
                'skills' => $agentData['skills'] ?? [],
            ],
        ]);

        Log::info('Агент импортирован', [
            'agent_id' => $agent->id,
            'name' => $agent->name,
            'slug' => $agent->slug,
            'category' => $category->name,
            'file' => $filePath,
        ]);

        return [
            'success' => true,
            'agent' => $agent,
        ];
    }

    /**
     * Распарсить файл агента
     */
    private function parseAgentFile(string $content, string $slug): ?array
    {
        $lines = explode("\n", $content);

        $name = '';
        $description = '';
        $personality = '';
        $expertise = [];
        $skills = [];
        $emoji = null;

        // Извлечь имя из первой строки (заголовок)
        foreach ($lines as $line) {
            $line = trim($line);

            // Пропустить пустые строки
            if (empty($line)) {
                continue;
            }

            // Найти заголовок (начинается с #)
            if (strpos($line, '#') === 0 && empty($name)) {
                $name = trim(str_replace('#', '', $line));
                continue;
            }

            // Найти описание (цитата)
            if (strpos($line, '>') === 0 && empty($description)) {
                $description = trim(substr($line, 1));
                continue;
            }

            // Найти эмодзи
            if (preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $line, $matches)) {
                $emoji = $matches[0];
            }

            // Найти ключевые слова для экспертизы
            if (stripos($line, 'expert') !== false || stripos($line, 'specialist') !== false) {
                $expertise[] = $line;
            }

            // Найти навыки
            if (stripos($line, 'skill') !== false || stripos($line, 'ability') !== false) {
                $skills[] = $line;
            }
        }

        // Если имя не найдено, использовать slug
        if (empty($name)) {
            $name = ucwords(str_replace('-', ' ', $slug));
        }

        // Если описание не найдено, создать дефолтное
        if (empty($description)) {
            $description = "Агент {$name} - специалист в своей области.";
        }

        return [
            'name' => $name,
            'description' => $description,
            'personality' => $personality,
            'expertise' => $expertise,
            'skills' => $skills,
            'emoji' => $emoji,
        ];
    }

    /**
     * Получить или создать категорию
     */
    private function getOrCreateCategory(string $categorySlug): Category
    {
        $category = Category::where('slug', $categorySlug)->first();

        if ($category) {
            return $category;
        }

        return Category::create([
            'name' => ucfirst(str_replace('-', ' ', $categorySlug)),
            'slug' => $categorySlug,
            'description' => 'Категория: ' . ucfirst(str_replace('-', ' ', $categorySlug)),
            'color' => $this->getCategoryColor($categorySlug),
            'icon' => $this->getCategoryIcon($categorySlug),
            'sector_x_min' => 0,
            'sector_x_max' => 200,
            'sector_y_min' => 0,
            'sector_y_max' => 200,
        ]);
    }

    /**
     * Создать зону по умолчанию
     */
    private function createDefaultZone(): OfficeZone
    {
        return OfficeZone::create([
            'name' => 'Рабочая зона',
            'slug' => 'workspace',
            'icon' => '💻',
            'color' => '#E0F2FE',
            'x_min' => 50,
            'x_max' => 750,
            'y_min' => 50,
            'y_max' => 550,
            'capacity' => 100,
            'amenities' => ['Wi-Fi', 'Розетки', 'Мониторы', 'Столы'],
        ]);
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

    /**
     * Получить статистику импорта
     */
    public function getImportStats(): array
    {
        $basePath = base_path('agency-agents');

        if (!File::isDirectory($basePath)) {
            return [
                'total_files' => 0,
                'categories' => [],
                'imported_agents' => Agent::count(),
            ];
        }

        $categories = File::directories($basePath);
        $totalFiles = 0;
        $categoryStats = [];

        foreach ($categories as $categoryPath) {
            $categorySlug = basename($categoryPath);

            // Пропустить служебные директории
            if (in_array($categorySlug, ['scripts', 'integrations', 'examples', '.github'])) {
                continue;
            }

            $files = File::glob($categoryPath . '/*.md');
            $fileCount = count($files);
            $totalFiles += $fileCount;

            $categoryStats[] = [
                'slug' => $categorySlug,
                'name' => ucfirst(str_replace('-', ' ', $categorySlug)),
                'file_count' => $fileCount,
                'imported_count' => Agent::whereHas('category', function ($query) use ($categorySlug) {
                    $query->where('slug', $categorySlug);
                })->count(),
            ];
        }

        return [
            'total_files' => $totalFiles,
            'categories' => $categoryStats,
            'imported_agents' => Agent::count(),
        ];
    }

    /**
     * Очистить всех агентов
     */
    public function clearAllAgents(): bool
    {
        try {
            Agent::truncate();
            Log::info('Все агенты удалены');
            return true;
        } catch (\Exception $e) {
            Log::error('Ошибка удаления агентов', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Переимпортировать всех агентов
     */
    public function reimportAll(): array
    {
        $this->clearAllAgents();
        return $this->importFromDirectory();
    }
}
