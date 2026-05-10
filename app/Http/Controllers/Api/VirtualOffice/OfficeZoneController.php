<?php

namespace App\Http\Controllers\Api\VirtualOffice;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice\OfficeZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OfficeZoneController extends Controller
{
    /**
     * Получить список всех зон
     */
    public function index(Request $request): JsonResponse
    {
        $query = OfficeZone::withCount(['agents', 'activeAgents']);

        // Поиск по имени
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $zones = $query->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Получить зону по ID
     */
    public function show(int $id): JsonResponse
    {
        $zone = OfficeZone::with(['agents.category', 'activeAgents.category'])
            ->withCount(['agents', 'activeAgents'])
            ->find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $zone,
        ]);
    }

    /**
     * Создать новую зону
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:office_zones,slug',
            'icon' => 'required|string|max:10',
            'color' => 'required|string|max:7',
            'x_min' => 'required|integer|min:0',
            'x_max' => 'required|integer|min:0|gt:x_min',
            'y_min' => 'required|integer|min:0',
            'y_max' => 'required|integer|min:0|gt:y_min',
            'capacity' => 'required|integer|min:1',
            'amenities' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $zone = OfficeZone::create($request->all());
        $zone->loadCount(['agents', 'activeAgents']);

        return response()->json([
            'success' => true,
            'message' => 'Зона успешно создана',
            'data' => $zone,
        ], 201);
    }

    /**
     * Обновить зону
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:office_zones,slug,' . $id,
            'icon' => 'sometimes|required|string|max:10',
            'color' => 'sometimes|required|string|max:7',
            'x_min' => 'sometimes|required|integer|min:0',
            'x_max' => 'sometimes|required|integer|min:0|gt:x_min',
            'y_min' => 'sometimes|required|integer|min:0',
            'y_max' => 'sometimes|required|integer|min:0|gt:y_min',
            'capacity' => 'sometimes|required|integer|min:1',
            'amenities' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $zone->update($request->all());
        $zone->loadCount(['agents', 'activeAgents']);

        return response()->json([
            'success' => true,
            'message' => 'Зона успешно обновлена',
            'data' => $zone,
        ]);
    }

    /**
     * Удалить зону
     */
    public function destroy(int $id): JsonResponse
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        // Проверить, есть ли агенты в зоне
        if ($zone->agents()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Невозможно удалить зону с агентами',
            ], 422);
        }

        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Зона успешно удалена',
        ]);
    }

    /**
     * Получить зону по slug
     */
    public function bySlug(string $slug): JsonResponse
    {
        $zone = OfficeZone::with(['agents.category', 'activeAgents.category'])
            ->withCount(['agents', 'activeAgents'])
            ->bySlug($slug)
            ->first();

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $zone,
        ]);
    }

    /**
     * Получить агентов в зоне
     */
    public function agents(int $id): JsonResponse
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        $agents = $zone->agents()->with(['category'])->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Получить активных агентов в зоне
     */
    public function activeAgents(int $id): JsonResponse
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
            ], 404);
        }

        $agents = $zone->activeAgents()->with(['category'])->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Проверить, находится ли точка в зоне
     */
    public function containsPoint(int $id, Request $request): JsonResponse
    {
        $zone = OfficeZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Зона не найдена',
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

        $contains = $zone->containsPoint($request->x, $request->y);

        return response()->json([
            'success' => true,
            'data' => [
                'contains' => $contains,
                'x' => $request->x,
                'y' => $request->y,
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'x_min' => $zone->x_min,
                    'x_max' => $zone->x_max,
                    'y_min' => $zone->y_min,
                    'y_max' => $zone->y_max,
                ],
            ],
        ]);
    }

    /**
     * Получить статистику зон
     */
    public function stats(): JsonResponse
    {
        $zones = OfficeZone::withCount(['agents', 'activeAgents'])->get();

        $stats = $zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'icon' => $zone->icon,
                'color' => $zone->color,
                'capacity' => $zone->capacity,
                'total_agents' => $zone->agents_count,
                'active_agents' => $zone->active_agents_count,
                'available_spots' => $zone->getAvailableSpots(),
                'is_full' => $zone->isFull(),
                'occupancy_rate' => $zone->capacity > 0
                    ? round(($zone->active_agents_count / $zone->capacity) * 100, 2)
                    : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
