<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SocialCareOrderController extends Controller
{
    /**
     * Create a new social care order.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string|in:Компаньон,Бытовая помощь,Уход,Сопровождение',
            'relation' => 'required|string|in:self,parent,relative',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'has_pets' => 'nullable|boolean',
            'mobility_issues' => 'nullable|boolean',
            'memory_issues' => 'nullable|boolean',
            'frequency' => 'required|string|in:once,weekly,daily',
            'duration' => 'required|integer|min:1|max:12',
            'time_slot' => 'required|string|in:morning,afternoon,evening',
            'language' => 'required|string|in:no,en,ru',
            'helper_gender' => 'required|string|in:any,female,male',
            'notes' => 'nullable|string|max:1000',
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',
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

            // Create address
            $address = Address::create([
                'street_address' => $request->address,
                'city' => 'Narvik',
                'postal_code' => '8500',
                'latitude' => 68.4372,
                'longitude' => 17.4256,
                'formatted_address' => $request->address.', Narvik',
            ]);

            // Find social care service type
            $serviceType = ServiceType::whereHas('serviceCategory', function ($q) {
                $q->whereIn('slug', ['social-help', 'social-care', 'social', 'care']);
            })->first();

            if (! $serviceType) {
                // Create a default service type if not found
                $serviceType = ServiceType::firstOrCreate(
                    ['code' => 'social_care_visit'],
                    [
                        'name' => 'Социальный уход',
                        'category' => 'social-care',
                        'is_active' => true,
                    ]
                );
            }

            // Calculate base price
            $basePrice = 500; // Base price per visit
            $hourlyRate = 300; // Per hour
            $totalPrice = $basePrice + ($request->duration * $hourlyRate);

            // Frequency multiplier
            if ($request->frequency === 'daily') {
                $totalPrice *= 0.9; // 10% discount for daily
            } elseif ($request->frequency === 'weekly') {
                $totalPrice *= 1.0; // Standard price
            }

            // Create main Order
            $order = Order::create([
                'user_id' => $customer->id,
                'address_id' => $address->id,
                'service_type' => $serviceType->code ?? 'social_care_visit',
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => round($totalPrice, 2),
                'currency' => 'NOK',
                'notes' => $request->notes,
                'metadata' => [
                    'service_type' => 'social_care_visit',
                    'care_details' => [
                        'service_type' => $request->service_type,
                        'relation' => $request->relation,
                        'beneficiary_name' => $request->name,
                        'has_pets' => $request->has_pets ?? false,
                        'mobility_issues' => $request->mobility_issues ?? false,
                        'memory_issues' => $request->memory_issues ?? false,
                        'frequency' => $request->frequency,
                        'duration' => $request->duration,
                        'time_slot' => $request->time_slot,
                        'language' => $request->language,
                        'helper_gender' => $request->helper_gender,
                    ],
                ],
            ]);

            // Create OrderItem
            $order->orderItems()->create([
                'service_type_id' => $serviceType->id,
                'quantity' => 1,
                'name' => $request->service_type,
                'unit_price' => round($totalPrice, 2),
                'total_price' => round($totalPrice, 2),
                'currency' => 'NOK',
                'metadata' => [
                    'frequency' => $request->frequency,
                    'duration' => $request->duration,
                    'time_slot' => $request->time_slot,
                ],
            ]);

            DB::commit();

            Log::info('Social care order created', [
                'order_id' => $order->id,
                'user_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => round($totalPrice, 2),
                    'currency' => 'NOK',
                ],
                'message' => 'Заявка на социальный уход успешно создана. Координатор свяжется с вами для подтверждения.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create social care order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать заявку',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
