<?php

namespace App\Services\VirtualOffice;

use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\Agent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    /**
     * Получить все категории
     */
    public function getAllCategories(): Collection
    {
        return Category::withCount(['agents', 'activeAgents'])->get();
    }

    /**
     * Получить категорию по ID
     */
    public function getCategory(int $id): ?Category
    {
        return Category::with(['agents.zone', 'activeAgents.zone'])
            ->withCount(['agents', 'activeAgents'])
            ->find($id);
    }

    /**
     * Получить категорию по slug
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        return Category::with(['agents.zone', 'activeAgents.zone'])
            ->withCount(['agents', 'activeAgents'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Создать новую категорию
     */
    public function createCategory(array $data): Category
    {
        $category = Category::create($data);

        Log::info('Категория создана', [
            'category_id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);

        return $category->loadCount(['agents', 'activeAgents']);
    }

    /**
     * Обновить категорию
     */
    public function updateCategory(int $id, array $data): ?Category
    {
        $category = Category::find($id);

        if (!$category) {
            return null;
        }

        $category->update($data);

        Log::info('Категория обновлена', [
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return $category->loadCount(['agents', 'activeAgents']);
    }

    /**
     * Удалить категорию
     */
    public function deleteCategory(int $id): bool
    {
        $category = Category::find($id);

        if (!$category) {
            return false;
        }

        // Проверить, есть ли агенты в категории
        if ($category->agents()->count() > 0) {
            Log::warning('Попытка удалить категорию с агентами', [
                'category_id' => $id,
                'name' => $category->name,
                'agent_count' => $category->agents()->count(),
            ]);
            return false;
        }

        $category->delete();

        Log::info('Категория удалена', [
            'category_id' => $id,
            'name' => $category->name,
        ]);

        return true;
    }

    /**
     * Получить агентов категории
     */
    public function getCategoryAgents(int $categoryId): Collection
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return collect();
        }

        return $category->agents()->with(['zone'])->get();
    }

    /**
     * Получить активных агентов категории
     */
    public function getCategoryActiveAgents(int $categoryId): Collection
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return collect();
        }

        return $category->activeAgents()->with(['zone'])->get();
    }

    /**
     * Проверить, находится ли точка в секторе категории
     */
    public function isPointInCategory(int $categoryId, int $x, int $y): bool
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return false;
        }

        return $category->containsPoint($x, $y);
    }

    /**
     * Получить категорию по координатам
     */
    public function getCategoryByCoordinates(int $x, int $y): ?Category
    {
        return Category::where('sector_x_min', '<=', $x)
            ->where('sector_x_max', '>=', $x)
            ->where('sector_y_min', '<=', $y)
            ->where('sector_y_max', '>=', $y)
            ->first();
    }

    /**
     * Получить случайную позицию в секторе категории
     */
    public function getRandomPositionInCategory(int $categoryId): ?array
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return null;
        }

        return $category->getRandomPosition();
    }

    /**
     * Получить статистику категорий
     */
    public function getCategoryStats(): array
    {
        $categories = Category::withCount(['agents', 'activeAgents'])->get();

        $stats = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'color' => $category->color,
                'total_agents' => $category->agents_count,
                'active_agents' => $category->active_agents_count,
                'sector' => [
                    'x_min' => $category->sector_x_min,
                    'x_max' => $category->sector_x_max,
                    'y_min' => $category->sector_y_min,
                    'y_max' => $category->sector_y_max,
                ],
            ];
        });

        return [
            'categories' => $stats,
            'total_categories' => $categories->count(),
            'total_agents' => $categories->sum('agents_count'),
            'total_active_agents' => $categories->sum('active_agents_count'),
        ];
    }

    /**
     * Получить размеры сектора категории
     */
    public function getCategorySectorSize(int $categoryId): ?array
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return null;
        }

        return [
            'width' => $category->sector_x_max - $category->sector_x_min,
            'height' => $category->sector_y_max - $category->sector_y_min,
        ];
    }

    /**
     * Получить центр сектора категории
     */
    public function getCategorySectorCenter(int $categoryId): ?array
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return null;
        }

        return [
            'x' => (int) (($category->sector_x_min + $category->sector_x_max) / 2),
            'y' => (int) (($category->sector_y_min + $category->sector_y_max) / 2),
        ];
    }

    /**
     * Получить категории по цвету
     */
    public function getCategoriesByColor(string $color): Collection
    {
        return Category::where('color', $color)->get();
    }

    /**
     * Получить категории по иконке
     */
    public function getCategoriesByIcon(string $icon): Collection
    {
        return Category::where('icon', $icon)->get();
    }

    /**
     * Получить категории с агентами
     */
    public function getCategoriesWithAgents(): Collection
    {
        return Category::withCount('agents')
            ->having('agents_count', '>', 0)
            ->get();
    }

    /**
     * Получить категории без агентов
     */
    public function getCategoriesWithoutAgents(): Collection
    {
        return Category::withCount('agents')
            ->having('agents_count', '=', 0)
            ->get();
    }

    /**
     * Получить случайную категорию
     */
    public function getRandomCategory(): ?Category
    {
        return Category::inRandomOrder()->first();
    }

    /**
     * Получить категории рядом с точкой
     */
    public function getCategoriesNearPoint(int $x, int $y, int $radius = 100): Collection
    {
        return Category::where(function ($query) use ($x, $y, $radius) {
            $query->whereBetween('sector_x_min', [$x - $radius, $x + $radius])
                  ->orWhereBetween('sector_x_max', [$x - $radius, $x + $radius])
                  ->orWhereBetween('sector_y_min', [$y - $radius, $y + $radius])
                  ->orWhereBetween('sector_y_max', [$y - $radius, $y + $radius]);
        })->get();
    }

    /**
     * Получить пересекающиеся категории
     */
    public function getOverlappingCategories(int $categoryId): Collection
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return collect();
        }

        return Category::where('id', '!=', $categoryId)
            ->where(function ($query) use ($category) {
                $query->where('sector_x_min', '<', $category->sector_x_max)
                      ->where('sector_x_max', '>', $category->sector_x_min)
                      ->where('sector_y_min', '<', $category->sector_y_max)
                      ->where('sector_y_max', '>', $category->sector_y_min);
            })
            ->get();
    }

    /**
     * Получить топ категорий по количеству агентов
     */
    public function getTopCategoriesByAgentCount(int $limit = 10): Collection
    {
        return Category::withCount('agents')
            ->orderBy('agents_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($category) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'agent_count' => $category->agents_count,
                ];
            });
    }

    /**
     * Получить топ категорий по количеству активных агентов
     */
    public function getTopCategoriesByActiveAgentCount(int $limit = 10): Collection
    {
        return Category::withCount('activeAgents')
            ->orderBy('active_agents_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($category) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'active_agent_count' => $category->active_agents_count,
                ];
            });
    }

    /**
     * Поиск категорий
     */
    public function searchCategories(string $query): Collection
    {
        return Category::where('name', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->get();
    }

    /**
     * Получить категории по описанию
     */
    public function getCategoriesByDescription(string $description): Collection
    {
        return Category::where('description', 'like', '%' . $description . '%')->get();
    }
}
