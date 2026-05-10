<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\ErrandTask;
use App\Models\Order;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Errand\ErrandPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ErrandTaskController extends Controller
{
    protected ErrandPricingService $pricingService;

    public function __construct(ErrandPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Create a new errand task order.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Customer info
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',

            // Task details
            'description' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'from_address' => 'required|array',
            'from_address.street' => 'required|string|max:255',
            'from_address.city' => 'required|string|max:100',
            'from_address.postal_code' => 'required|string|max:10',
            'from_address.lat' => 'nullable|numeric|between:-90,90',
            'from_address.lng' => 'nullable|numeric|between:-180,180',

            'to_address' => 'nullable|array',
            'to_address.street' => 'nullable|string|max:255',
            'to_address.city' => 'nullable|string|max:100',
            'to_address.postal_code' => 'nullable|string|max:10',
            'to_address.lat' => 'nullable|numeric|between:-90,90',
            'to_address.lng' => 'nullable|numeric|between:-180,180',

            // Task options
            'duration_hours' => 'required|numeric|min:0.5|max:10',
            'is_urgent' => 'nullable|boolean',
            'purchase_budget' => 'nullable|numeric|min:0',
            'scheduled_at' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create or find customer
            $customer = User::firstOrCreate(
                ['email' => $request->customer['email']],
                [
                    'name' => $request->customer['name'],
                    'phone' => $request->customer['phone'],
                    'password' => bcrypt(uniqid()),
                    'is_active' => true,
                ]
            );

            // Default coordinates for Narvik if not provided
            $fromLat = $request->from_address['lat'] ?? 68.4372;
            $fromLng = $request->from_address['lng'] ?? 17.4256;
            $toLat = $request->to_address['lat'] ?? ($request->to_address ? 68.4372 : null);
            $toLng = $request->to_address['lng'] ?? ($request->to_address ? 17.4256 : null);

            // Create from address
            $fromAddress = Address::create([
                'street_address' => $request->from_address['street'],
                'city' => $request->from_address['city'],
                'postal_code' => $request->from_address['postal_code'],
                'latitude' => $fromLat,
                'longitude' => $fromLng,
                'formatted_address' => $this->formatAddress($request->from_address),
            ]);

            // Create to address if provided
            $toAddress = null;
            if ($request->to_address && $request->to_address['street']) {
                $toAddress = Address::create([
                    'street_address' => $request->to_address['street'],
                    'city' => $request->to_address['city'] ?? 'Narvik',
                    'postal_code' => $request->to_address['postal_code'] ?? '',
                    'latitude' => $toLat,
                    'longitude' => $toLng,
                    'formatted_address' => $this->formatAddress($request->to_address),
                ]);
            }

            // Find errand service type
            $errandService = ServiceType::whereHas('serviceCategory', function ($q) {
                $q->whereIn('slug', ['personal-task', 'errands', 'errand']);
            })->first();

            if (! $errandService) {
                throw new \Exception('Errand service type not found');
            }

            // Calculate distance
            $distanceKm = 0;
            if ($toAddress) {
                $distanceKm = $this->calculateDistance($fromLat, $fromLng, $toLat, $toLng);
            }

            // Create ErrandOrderDetails for pricing calculation
            $errandDetails = new \App\Models\ErrandOrderDetails([
                'expected_duration_minutes' => (int) ($request->duration_hours * 60),
                'is_urgent' => $request->is_urgent ?? false,
                'material_advance_amount' => (int) ($request->purchase_budget ?? 0),
                'complexity_level' => 1, // Default complexity
                'requires_trusted_helper' => false,
            ]);

            // Calculate pricing (returns in minor units, convert to NOK)
            $pricing = $this->pricingService->estimate($errandDetails, $distanceKm);
            $totalAmountNOK = ($pricing['total_estimated_price'] ?? 0) / 100; // Convert from øre to NOK

            // Create main Order
            $order = Order::create([
                'user_id' => $customer->id,
                'address_id' => $toAddress ? $toAddress->id : $fromAddress->id,
                'service_type' => $errandService->code ?? 'errand',
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
                'total_amount' => round($totalAmountNOK, 2),
                'currency' => 'NOK',
                'metadata' => [
                    'errand_task' => true,
                    'category' => $request->category,
                ],
            ]);

            // Create ErrandTask
            $errandTask = ErrandTask::create([
                'order_id' => $order->id,
                'title' => $request->description,
                'category' => $request->category ?? 'other',
                'description' => $request->description,
                'status' => 'pending',
                'priority' => ($request->is_urgent ?? false) ? 'high' : 'normal',
                'customer_name' => $request->customer['name'],
                'customer_phone' => $request->customer['phone'],
                'pickup_address' => $this->formatAddress($request->from_address),
                'dropoff_address' => $toAddress ? $this->formatAddress($request->to_address) : null,
                'from_location' => [
                    'lat' => $fromLat,
                    'lng' => $fromLng,
                    'address' => $this->formatAddress($request->from_address),
                ],
                'to_location' => $toAddress ? [
                    'lat' => $toLat,
                    'lng' => $toLng,
                    'address' => $this->formatAddress($request->to_address),
                ] : null,
                'is_urgent' => $request->is_urgent ?? false,
                'expected_duration_minutes' => (int) ($request->duration_hours * 60),
                'expected_distance_km' => round($distanceKm, 2),
                'material_advance_amount' => (int) ($request->purchase_budget ?? 0),
                'base_fee' => round(($pricing['base_fee'] ?? 5000) / 100, 2), // Convert from øre to NOK
                'time_fee' => round(($pricing['time_fee'] ?? 0) / 100, 2),
                'distance_fee' => round(($pricing['distance_fee'] ?? 0) / 100, 2),
                'urgency_fee' => round(($pricing['urgency_fee'] ?? 0) / 100, 2),
                'estimated_total_amount' => round($totalAmountNOK, 2),
                'scheduled_at' => $request->scheduled_at,
                'notes' => $request->notes,
                'pricing_snapshot' => $pricing,
            ]);

            // Create OrderItem
            $order->orderItems()->create([
                'service_type_id' => $errandService->id,
                'quantity' => 1,
                'name' => 'Errand Task: '.($request->category ?? 'Personal Task'),
                'unit_price' => round($totalAmountNOK, 2),
                'total_price' => round($totalAmountNOK, 2),
                'currency' => 'NOK',
                'metadata' => [
                    'errand_task_id' => $errandTask->id,
                    'duration_hours' => $request->duration_hours,
                    'is_urgent' => $request->is_urgent ?? false,
                ],
            ]);

            DB::commit();

            Log::info('Errand task created', [
                'order_id' => $order->id,
                'errand_task_id' => $errandTask->id,
                'user_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'errand_task_id' => $errandTask->id,
                    'order_number' => $order->order_number,
                    'estimated_total_amount' => round($totalAmountNOK, 2),
                    'currency' => 'NOK',
                ],
                'message' => 'Errand task created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create errand task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create errand task',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Calculate price estimate for errand task.
     */
    public function estimate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'duration_hours' => 'required|numeric|min:0.5|max:10',
            'distance_km' => 'nullable|numeric|min:0',
            'is_urgent' => 'nullable|boolean',
            'purchase_budget' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create ErrandOrderDetails for pricing calculation
            $errandDetails = new \App\Models\ErrandOrderDetails([
                'expected_duration_minutes' => (int) ($request->duration_hours * 60),
                'is_urgent' => $request->is_urgent ?? false,
                'material_advance_amount' => (int) ($request->purchase_budget ?? 0),
                'complexity_level' => 1, // Default complexity
                'requires_trusted_helper' => false,
            ]);

            $pricing = $this->pricingService->estimate($errandDetails, $request->distance_km ?? 0);

            // Convert from øre to NOK for response
            $pricingNOK = [
                'base_fee' => round(($pricing['base_fee'] ?? 0) / 100, 2),
                'time_fee' => round(($pricing['time_fee'] ?? 0) / 100, 2),
                'distance_fee' => round(($pricing['distance_fee'] ?? 0) / 100, 2),
                'urgency_fee' => round(($pricing['urgency_fee'] ?? 0) / 100, 2),
                'estimated_total_amount' => round(($pricing['total_estimated_price'] ?? 0) / 100, 2),
                'currency' => 'NOK',
            ];

            return response()->json([
                'success' => true,
                'data' => $pricingNOK,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to estimate errand task price', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to estimate price',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Format address string.
     */
    protected function formatAddress(array $address): string
    {
        return sprintf(
            '%s, %s %s',
            $address['street'],
            $address['postal_code'],
            $address['city']
        );
    }

    /**
     * Calculate distance between two points in kilometers.
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
