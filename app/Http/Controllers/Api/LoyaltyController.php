<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    /**
     * Get user loyalty balance
     */
    public function index(Request $request): JsonResponse
    {
        $balance = $request->user()->getOrCreateLoyaltyBalance();

        return response()->json([
            'data' => [
                'current_points' => $balance->points,
                'lifetime_points' => $balance->lifetime_points,
                'points_value' => $balance->getPointsValue($balance->points),
                'updated_at' => $balance->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Show loyalty transactions
     */
    public function show(Request $request): JsonResponse
    {
        $page = $request->query('page', 1);
        $per_page = min($request->query('per_page', 20), 100);

        $balance = $request->user()->getOrCreateLoyaltyBalance();
        $transactions = $balance->transactions()
            ->latest()
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'data' => $transactions->map(function (LoyaltyTransaction $tx) {
                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'type_label' => $tx->getTypeLabel(),
                    'type_color' => $tx->getTypeColor(),
                    'type_icon' => $tx->getTypeIcon(),
                    'points_amount' => $tx->points_amount,
                    'description' => $tx->description,
                    'source_type' => $tx->source_type,
                    'created_at' => $tx->created_at->toIso8601String(),
                ];
            })->toArray(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Redeem loyalty points
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $balance = $request->user()->getOrCreateLoyaltyBalance();

        if (! $balance->hasEnoughPoints($validated['points'])) {
            return response()->json([
                'success' => false,
                'message' => 'Недостатньо балів для цієї операції',
                'current_points' => $balance->points,
                'requested_points' => $validated['points'],
            ], 422);
        }

        $reason = $validated['reason'] ?? 'Витрачено балів через API';
        $success = $balance->redeemPoints($validated['points'], $reason);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Бали успішно витрачені' : 'Помилка при витраті балів',
            'remaining_points' => $balance->fresh()->points,
            'points_value' => $balance->fresh()->getPointsValue($balance->fresh()->points),
        ]);
    }
}
