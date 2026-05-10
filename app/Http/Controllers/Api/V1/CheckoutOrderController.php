<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderScenarioRegistry;
use App\Services\Orders\UnifiedOrderEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutOrderController extends Controller
{
    public function scenarios(OrderScenarioRegistry $registry): JsonResponse
    {
        $category = request()->query('category');
        $scenarios = $registry->enabled(is_string($category) ? $category : null);

        return response()->json([
            'success' => true,
            'data' => array_map(fn (array $scenario) => $registry->publicMetadata($scenario), $scenarios),
        ]);
    }

    public function store(string $scenario, Request $request, UnifiedOrderEngine $engine): JsonResponse
    {
        $data = $request->validate([
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'pickup_location' => ['nullable', 'array'],
            'delivery_location' => ['nullable', 'array'],
            'delivery_window' => ['nullable', 'string', 'max:120'],
            'slot' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_urgent' => ['nullable', 'boolean'],
            'store_id' => ['nullable', 'integer'],
            'restaurant_id' => ['nullable', 'integer'],
            'classified_ad_id' => ['nullable', 'integer'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $scenarioConfig = app(OrderScenarioRegistry::class)->getEnabled($scenario);

        $missing = app(OrderScenarioRegistry::class)->validateRequiredFields($scenarioConfig, $data);
        if ($missing !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields.',
                'missing_fields' => $missing,
            ], 422);
        }

        $order = $engine->create($scenario, $request->user(), $data);

        return response()->json([
            'success' => true,
            'data' => $order->load(['deliveryOrder', 'user', 'assignedUser']),
            'message' => 'Order created through Unified Order Engine.',
        ], 201);
    }
}
