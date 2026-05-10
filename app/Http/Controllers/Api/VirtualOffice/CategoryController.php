<?php

namespace App\Http\Controllers\Api\VirtualOffice;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount(['agents', 'activeAgents']);

        // Поиск по имени
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Получить категорию по ID
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::with(['agents.zone', 'activeAgents.zone'])
            ->withCount(['agents', 'activeAgents'])
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Создать новую категорию
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'icon' => 'required|string|max:10',
            'sector_x_min' => 'required|integer|min:0',
            'sector_x_max' => 'required|integer|min:0|gt:sector_x_min',
            'sector_y_min' => 'required|integer|min:0',
            'sector_y_max' => 'required|integer|min:0|gt:sector_y_min',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = Category::create($request->all());
        $category->loadCount(['agents', 'activeAgents']);

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно создана',
            'data' => $category,
        ], 201);
    }

    /**
     * Обновить категорию
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'color' => 'sometimes|required|string|max:7',
            'icon' => 'sometimes|required|string|max:10',
            'sector_x_min' => 'sometimes|required|integer|min:0',
            'sector_x_max' => 'sometimes|required|integer|min:0|gt:sector_x_min',
            'sector_y_min' => 'sometimes|required|integer|min:0',
            'sector_y_max' => 'sometimes|required|integer|min:0|gt:sector_y_min',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update($request->all());
        $category->loadCount(['agents', 'activeAgents']);

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно обновлена',
            'data' => $category,
        ]);
    }

    /**
     * Удалить категорию
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        // Проверить, есть ли агенты в категории
        if ($category->agents()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Невозможно удалить категорию с агентами',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно удалена',
        ]);
    }

    /**
     * Получить категорию по slug
     */
    public function bySlug(string $slug): JsonResponse
    {
        $category = Category::with(['agents.zone', 'activeAgents.zone'])
            ->withCount(['agents', 'activeAgents'])
            ->bySlug($slug)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Получить агентов категории
     */
    public function agents(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $agents = $category->agents()->with(['zone'])->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Получить активных агентов категории
     */
    public function activeAgents(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $agents = $category->activeAgents()->with(['zone'])->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Проверить, находится ли точка в секторе категории
     */
    public function containsPoint(int $id, Request $request): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'x' => 'required|integer|min:0|max:800',
            'y' => 'required|integer|min:0|max:600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contains = $category->containsPoint($request->x, $request->y);

        return response()->json([
            'success' => true,
            'data' => [
                'contains' => $contains,
                'x' => $request->x,
                'y' => $request->y,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'sector_x_min' => $category->sector_x_min,
                    'sector_x_max' => $category->sector_x_max,
                    'sector_y_min' => $category->sector_y_min,
                    'sector_y_max' => $category->sector_y_max,
                ],
            ],
        ]);
    }

    /**
     * Получить случайную позицию в секторе категории
     */
    public function randomPosition(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $position = $category->getRandomPosition();

        return response()->json([
            'success' => true,
            'data' => [
                'x' => $position['x'],
                'y' => $position['y'],
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ],
        ]);
    }

    /**
     * Получить статистику категорий
     */
    public function stats(): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
