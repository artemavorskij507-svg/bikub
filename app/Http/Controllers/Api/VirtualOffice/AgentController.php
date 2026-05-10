<?php

namespace App\Http\Controllers\Api\VirtualOffice;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Получить список всех агентов
     */
    public function index(Request $request): JsonResponse
    {
        $query = Agent::with(['category', 'zone']);

        // Фильтрация по категории
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Фильтрация по зоне
        if ($request->has('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Поиск по имени
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Пагинация
        $perPage = $request->get('per_page', 50);
        $agents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $agents->items(),
            'meta' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
            ],
        ]);
    }

    /**
     * Получить агента по ID
     */
    public function show(int $id): JsonResponse
    {
        $agent = Agent::with(['category', 'zone', 'tasks', 'messages'])->find($id);

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
     * Создать нового агента
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:agents,slug',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'zone_id' => 'required|exists:office_zones,id',
            'x_position' => 'required|integer|min:0|max:800',
            'y_position' => 'required|integer|min:0|max:600',
            'avatar' => 'nullable|string|max:500',
            'emoji' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agent = Agent::create($request->all());
        $agent->load(['category', 'zone']);

        return response()->json([
            'success' => true,
            'message' => 'Агент успешно создан',
            'data' => $agent,
        ], 201);
    }

    /**
     * Обновить агента
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:agents,slug,' . $id,
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'zone_id' => 'sometimes|required|exists:office_zones,id',
            'x_position' => 'sometimes|required|integer|min:0|max:800',
            'y_position' => 'sometimes|required|integer|min:0|max:600',
            'avatar' => 'nullable|string|max:500',
            'emoji' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'is_active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agent->update($request->all());
        $agent->load(['category', 'zone']);

        return response()->json([
            'success' => true,
            'message' => 'Агент успешно обновлен',
            'data' => $agent,
        ]);
    }

    /**
     * Удалить агента
     */
    public function destroy(int $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $agent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Агент успешно удален',
        ]);
    }

    /**
     * Переместить агента
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
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

        $agent->moveTo($request->x, $request->y);

        return response()->json([
            'success' => true,
            'message' => 'Агент перемещен',
            'data' => [
                'id' => $agent->id,
                'x_position' => $agent->x_position,
                'y_position' => $agent->y_position,
            ],
        ]);
    }

    /**
     * Переместить агента в зону
     */
    public function moveToZone(Request $request, int $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агент не найден',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:office_zones,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $zone = OfficeZone::find($request->zone_id);
        $agent->moveToZone($zone);

        return response()->json([
            'success' => true,
            'message' => 'Агент перемещен в зону',
            'data' => [
                'id' => $agent->id,
                'zone_id' => $agent->zone_id,
                'x_position' => $agent->x_position,
                'y_position' => $agent->y_position,
            ],
        ]);
    }

    /**
     * Получить агентов по категории
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена',
            ], 404);
        }

        $agents = Agent::with(['zone'])
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Получить агентов по зоне
     */
    public function byZone(int $zoneId): JsonResponse
    {
        $zone = OfficeZone::find($zoneId);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        $agents = Agent::with(['category'])
            ->where('zone_id', $zoneId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Получить статистику агентов
     */
    public function stats(): JsonResponse
    {
        $totalAgents = Agent::count();
        $activeAgents = Agent::where('is_active', true)->count();
        $agentsByCategory = Category::withCount('agents')->get();
        $agentsByZone = OfficeZone::withCount('agents')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalAgents,
                'active' => $activeAgents,
                'inactive' => $totalAgents - $activeAgents,
                'by_category' => $agentsByCategory,
                'by_zone' => $agentsByZone,
            ],
        ]);
    }
}
