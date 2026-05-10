<?php

namespace App\Http\Controllers;

use App\Http\Requests\EcoDisposalEstimateRequest;
use App\Http\Requests\EcoDisposalOrderRequest;
use App\Models\DisposalItem;
use App\Services\EcoDisposal\EcoDisposalOrderService;
use App\Services\EcoDisposal\EcoDisposalPricingService;
use Illuminate\Http\JsonResponse;

class EcoDisposalController extends Controller
{
    public function __construct(
        protected EcoDisposalPricingService $pricingService,
        protected EcoDisposalOrderService $orderService
    ) {}

    public function index()
    {
        $items = DisposalItem::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('eco-disposal.index', [
            'items' => $items,
        ]);
    }

    public function estimate(EcoDisposalEstimateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $dto = $this->pricingService->estimate(
                $validated['items'] ?? [],
                $validated['floor'] ?? null,
                (bool) ($validated['has_elevator'] ?? false),
                $validated['parking_distance_m'] ?? null,
                (bool) ($validated['express_requested'] ?? false),
                $validated['zone_code'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_volume_m3' => $dto->estimatedVolumeM3,
                    'estimated_weight_kg' => $dto->estimatedWeightKg,
                    'base_price_nok' => $dto->basePriceNok,
                    'difficulty_coefficient' => $dto->difficultyCoefficient,
                    'express_surcharge_nok' => $dto->expressSurchargeNok,
                    'distance_surcharge_nok' => $dto->distanceSurchargeNok,
                    'total_price_nok' => $dto->totalPriceNok,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to estimate: '.$e->getMessage(),
            ], 422);
        }
    }

    public function store(EcoDisposalOrderRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user(); // adjust if guest orders are allowed

        try {
            $order = $this->orderService->createEcoDisposalOrder(
                user: $user,
                items: $validated['items'],
                floor: $validated['floor'] ?? null,
                hasElevator: (bool) ($validated['has_elevator'] ?? false),
                parkingDistanceM: $validated['parking_distance_m'] ?? null,
                expressRequested: (bool) ($validated['express_requested'] ?? false),
                addressData: [
                    'address_line' => $validated['address_line'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'city' => $validated['city'] ?? null,
                    // extend with coordinates if available in project
                ],
                zoneCode: $validated['zone_code'] ?? null,
            );

            // TODO: Initiate payment intent (Stripe/Vipps) using existing payment service flow and redirect.
            // For now, redirect to a generic order view/confirmation if exists, otherwise home.
            if (route()->has('orders.view')) {
                return redirect()->route('orders.view', $order)->with('success', 'Заказ создан, перейдите к оплате.');
            }

            return redirect()->route('public.home')->with('success', 'Заказ создан, перейдите к оплате.');
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => 'Не удалось создать заказ: '.$e->getMessage()])->withInput();
        }
    }
}
