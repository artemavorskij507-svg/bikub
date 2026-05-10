<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RoadsideAssistanceDetail;
use App\Models\RoadsideEmergency;
use App\Models\RoadsidePreset;
use App\Models\User;
use App\Models\VehicleInspectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RoadsideController extends Controller
{
    public function index()
    {
        \Log::info('RoadsideController@index called', ['path' => request()->path()]);

        try {
            // Check if table exists
            if (! Schema::hasTable('roadside_presets')) {
                \Log::warning('roadside_presets table does not exist');
                $presets = collect();
            } else {
                $presets = RoadsidePreset::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->groupBy('service_type');
            }

            return view('public.roadside.index', compact('presets'));
        } catch (\Exception $e) {
            \Log::error('RoadsideController@index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return empty collection if error
            return view('public.roadside.index', ['presets' => collect()]);
        }
    }

    public function orderForm(Request $request)
    {
        try {
            $presets = RoadsidePreset::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            // Если пользователь авторизован, подставляем его данные
            $user = auth()->user();
            $userData = $user ? [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ] : [];

            return view('public.roadside.order', compact('presets', 'userData'));
        } catch (\Exception $e) {
            \Log::error('RoadsideController@orderForm error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('public.roadside.order', [
                'presets' => collect(),
                'userData' => [],
            ]);
        }
    }

    public function submitOrder(Request $request)
    {
        $validated = $request->validate([
            'preset_id' => ['required', 'exists:roadside_presets,id'],
            'incident_address' => ['required', 'string', 'min:3', 'max:500'],
            'vehicle_make' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            DB::beginTransaction();

            // 1. Найти пресет
            $preset = RoadsidePreset::findOrFail($validated['preset_id']);

            // 2. Получить или создать пользователя
            $user = auth()->user();
            if (! $user) {
                // Создаем временного пользователя
                $user = User::create([
                    'name' => 'Roadside Client',
                    'email' => 'roadside_'.time().'_'.rand(1000, 9999).'@temp.local',
                    'password' => bcrypt(uniqid()),
                    'is_active' => true,
                ]);
            }

            // 3. Получить текущую зону из сессии (если есть)
            $geoZoneId = session('current_zone_id') ?? session('zone_id') ?? null;

            // 4. Создать Order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'priority' => 'normal',
                'currency' => 'NOK',
                'payment_status' => 'pending',
                'geo_zone_id' => $geoZoneId,
                'total_amount' => $preset->base_price ?? 0,
                'metadata' => [
                    'source' => 'public_roadside',
                    'channel' => 'website-roadside',
                    'roadside_preset_id' => $preset->id,
                ],
            ]);

            // 5. Создать RoadsideAssistanceDetail
            RoadsideAssistanceDetail::create([
                'order_id' => $order->id,
                'subtype' => $preset->code,
                'incident_address' => $validated['incident_address'],
                'vehicle_make' => $validated['vehicle_make'] ?? null,
                'vehicle_model' => $validated['vehicle_model'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'extra' => [],
            ]);

            DB::commit();

            Log::info('Roadside order created', [
                'order_id' => $order->id,
                'preset_id' => $preset->id,
            ]);

            // Редирект на страницу "Спасибо"
            return redirect()
                ->route('public.roadside.thanks')
                ->with('order_id', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Roadside order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось создать заявку. Пожалуйста, попробуйте еще раз или свяжитесь с поддержкой.']);
        }
    }

    /**
     * Show thanks page after order submission.
     */
    public function thanks()
    {
        $orderId = session('order_id');
        $order = $orderId ? Order::with('roadsideDetails')->find($orderId) : null;

        return view('public.roadside.thanks', compact('order'));
    }

    /**
     * Show public form for roadside assistance / towing.
     */
    public function showForm()
    {
        return view('public.roadside.form');
    }

    /**
     * Submit public roadside request.
     */
    public function submit(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:roadside,evacuator,inspection'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'problem' => ['required', 'string', 'max:2000'],
            'location_text' => ['required', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        try {
            DB::beginTransaction();

            // 1. Determine service type
            $serviceTypeCode = match ($data['type']) {
                'roadside' => 'roadside_assistance',
                'evacuator' => 'vehicle_transport',
                'inspection' => 'vehicle_inspection',
            };

            // 2. Get or create user
            $user = auth()->user();
            if (! $user) {
                // Create temporary user or use guest
                $user = User::firstOrCreate(
                    ['email' => 'roadside_'.md5($data['phone']).'@temp.local'],
                    [
                        'name' => $data['full_name'],
                        'phone' => $data['phone'],
                        'password' => bcrypt(uniqid()),
                    ]
                );
            }

            // 3. Determine incident type for emergency
            // For roadside, try to detect from problem description
            $incidentType = match ($data['type']) {
                'roadside' => $this->detectIncidentType($data['problem']),
                'evacuator' => 'tow_needed',
                'inspection' => null, // Not applicable for inspection
            };

            // 4. Create RoadsideEmergency or VehicleInspectionRequest
            if ($data['type'] === 'inspection') {
                // For inspection, create VehicleInspectionRequest
                // Get first active preset as default
                $preset = \App\Models\VehicleInspectionPreset::where('is_active', true)->first();

                $inspectionRequest = \App\Models\VehicleInspectionRequest::create([
                    'customer_id' => $user->id,
                    'preset_id' => $preset?->id,
                    'seller_name' => $data['full_name'],
                    'seller_phone' => $data['phone'],
                    'vehicle_make' => null, // Can be extracted from problem if needed
                    'vehicle_model' => null,
                    'address' => $data['location_text'],
                    'status' => 'pending',
                    'metadata' => [
                        'source' => 'public_form',
                        'type' => $data['type'],
                        'full_name' => $data['full_name'],
                        'phone' => $data['phone'],
                        'vehicle_plate' => $data['vehicle_plate'] ?? null,
                        'vehicle_type' => $data['vehicle_type'] ?? null,
                        'problem' => $data['problem'],
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);

                // Create Order for inspection
                $orderService = app(\App\Services\RoadsideOrderService::class);
                $order = $orderService->createOrderFromInspectionRequest($inspectionRequest);

                // Send notification
                $this->notifyDispatchersForInspection($inspectionRequest, $order);

                DB::commit();

                Log::info('Public inspection request created', [
                    'inspection_request_id' => $inspectionRequest->id,
                    'order_id' => $order->id ?? null,
                ]);

                return redirect()
                    ->route('public.roadside.form')
                    ->with('status', 'Мы получили ваш запрос на осмотр. Диспетчер свяжется с вами в ближайшее время.');
            } else {
                $emergency = RoadsideEmergency::create([
                    'customer_id' => $user->id,
                    'incident_type' => $incidentType,
                    'incident_description' => $data['problem'],
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'status' => 'new',
                    'metadata' => [
                        'source' => 'public_form',
                        'type' => $data['type'],
                        'full_name' => $data['full_name'],
                        'phone' => $data['phone'],
                        'vehicle_plate' => $data['vehicle_plate'] ?? null,
                        'vehicle_type' => $data['vehicle_type'] ?? null,
                        'location_text' => $data['location_text'],
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);

                // Generate tracking token and URL
                $emergency->generateTrackingToken();

                // 5. Create Order immediately
                $orderService = app(\App\Services\RoadsideOrderService::class);
                $order = $orderService->createOrderFromEmergency($emergency);

                // Ensure order is set
                if (! $order) {
                    throw new \Exception('Failed to create order from emergency');
                }

                // 6. Send notification to dispatchers
                $this->notifyDispatchers($emergency, $order);
            }

            DB::commit();

            Log::info('Public roadside request created', [
                'emergency_id' => $emergency->id,
                'order_id' => $order->id ?? null,
                'type' => $data['type'],
            ]);

            return redirect()
                ->route('public.roadside.form')
                ->with('status', 'Мы получили ваш запрос. Диспетчер свяжется с вами в ближайшее время.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Public roadside request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось отправить запрос. Пожалуйста, попробуйте еще раз или свяжитесь с поддержкой по телефону.']);
        }
    }

    /**
     * Notify dispatchers about new roadside request.
     */
    protected function notifyDispatchers(RoadsideEmergency $emergency, Order $order): void
    {
        try {
            $dispatchers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'dispatcher']);
            })->get();

            foreach ($dispatchers as $dispatcher) {
                $dispatcher->notify(new \App\Notifications\RoadsideNewRequestNotification($emergency, $order));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify dispatchers', [
                'error' => $e->getMessage(),
                'emergency_id' => $emergency->id,
            ]);
        }
    }

    /**
     * Notify dispatchers about new inspection request.
     */
    protected function notifyDispatchersForInspection(VehicleInspectionRequest $request, Order $order): void
    {
        try {
            $dispatchers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'dispatcher']);
            })->get();

            foreach ($dispatchers as $dispatcher) {
                // Use database notification directly
                $customerName = $request->customer->name ?? ($request->metadata['full_name'] ?? 'N/A');
                $customerPhone = $request->metadata['phone'] ?? 'N/A';

                $dispatcher->notifications()->create([
                    'type' => 'roadside_new_request',
                    'data' => [
                        'type' => 'roadside_new_request',
                        'title' => 'Новый запрос: Осмотр авто',
                        'message' => "Клиент: {$customerName}, Телефон: {$customerPhone}",
                        'inspection_request_id' => $request->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'url' => \App\Filament\Resources\VehicleInspectionRequestResource::getUrl('edit', ['record' => $request]),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify dispatchers for inspection', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
            ]);
        }
    }

    /**
     * Detect incident type from problem description.
     */
    protected function detectIncidentType(string $problem): string
    {
        $problem = mb_strtolower($problem);

        if (preg_match('/\b(прикур|заряд|батаре|аккумулятор)\b/ui', $problem)) {
            return 'jump_start';
        }
        if (preg_match('/\b(колес|шина|прокол|плоск)\b/ui', $problem)) {
            return 'flat_tire';
        }
        if (preg_match('/\b(топлив|бензин|дизель|закончил)\b/ui', $problem)) {
            return 'fuel';
        }
        if (preg_match('/\b(ключ|заперт|закрыт)\b/ui', $problem)) {
            return 'locked_keys';
        }
        if (preg_match('/\b(не заводит|не запускает|не стартует)\b/ui', $problem)) {
            return 'engine_no_start';
        }
        if (preg_match('/\b(дтп|авария|столкновение|удар)\b/ui', $problem)) {
            return 'accident';
        }

        // Default
        return 'engine_no_start';
    }
}
