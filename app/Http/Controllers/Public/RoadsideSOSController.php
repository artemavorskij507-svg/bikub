<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\RoadsideAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoadsideSOSController extends Controller
{
    protected $assignmentService;

    public function __construct(RoadsideAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Show the SOS form.
     */
    public function index()
    {
        return view('public.roadside.sos');
    }

    /**
     * Handle SOS form submission.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'incident_type' => 'required|in:jump_start,fuel,flat_tire,locked_keys,engine_no_start,tow_needed,accident',
            'phone' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:4096',
        ]);

        try {
            DB::beginTransaction();

            // 1. Определение/создание пользователя
            $customer = auth()->user();

            if (! $customer) {
                $customer = User::firstOrCreate(
                    ['phone' => $validated['phone']],
                    [
                        'name' => $validated['name'] ?? 'Roadside Client',
                        'email' => 'roadside_'.time().'_'.rand(1000, 9999).'@temp.local',
                        'password' => bcrypt(Str::random(10)),
                        'is_active' => true,
                    ]
                );
            }

            // 2. Сохранение фото
            $photos = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store('emergency_photos', 'public');
                    $photos[] = $path;
                }
            }

            // 3. Создание RoadsideEmergency
            $emergency = RoadsideEmergency::create([
                'customer_id' => $customer->id,
                'incident_type' => $validated['incident_type'],
                'incident_description' => $validated['description'] ?? null,
                'photos' => $photos,
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'new',
                'metadata' => [
                    'vehicle_make' => $validated['vehicle_make'] ?? null,
                    'vehicle_model' => $validated['vehicle_model'] ?? null,
                    'submitted_at' => now()->toIso8601String(),
                ],
            ]);

            // Generate tracking token and URL
            $emergency->generateTrackingToken();

            // 4. Автоназначение Road Helper или Partner
            $this->assignmentService->assign($emergency);

            // 5. Создание Order на основе Emergency
            $this->createOrderFromEmergency($emergency);

            DB::commit();

            Log::info('Roadside SOS emergency created', [
                'emergency_id' => $emergency->id,
                'customer_id' => $customer->id,
                'incident_type' => $emergency->incident_type,
            ]);

            return redirect()
                ->route('public.roadside.sos.success', ['emergency' => $emergency->id])
                ->with('status', 'emergency_created');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Roadside SOS creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось создать запрос на помощь. Пожалуйста, попробуйте еще раз или свяжитесь с поддержкой.']);
        }
    }

    /**
     * Show success page after emergency creation.
     */
    public function success(RoadsideEmergency $emergency)
    {
        $emergency->load(['customer', 'helper.user', 'partner', 'order']);

        return view('public.roadside.success', compact('emergency'));
    }

    /**
     * Create Order from Emergency.
     */
    protected function createOrderFromEmergency(RoadsideEmergency $emergency)
    {
        // Найти ServiceType для ROAD_ASSIST
        $serviceType = ServiceType::where(function ($q) {
            $q->where('code', 'road_assist')
                ->orWhere('slug', 'road_assist')
                ->orWhere('category', 'roadside_assistance');
        })->where('is_active', true)->first();

        if (! $serviceType) {
            // Создать временный ServiceType если не найден
            $serviceType = ServiceType::firstOrCreate(
                ['code' => 'road_assist'],
                [
                    'name' => 'Roadside Assistance',
                    'slug' => 'road_assist',
                    'category' => 'roadside_assistance',
                    'is_active' => true,
                    'description' => 'Emergency roadside assistance service',
                ]
            );
        }

        // Получить текущую зону из сессии (если есть)
        $geoZoneId = session('current_zone_id') ?? session('zone_id') ?? null;

        // Создать Order
        $order = Order::create([
            'user_id' => $emergency->customer_id,
            'status' => $emergency->status === 'assigned' ? 'assigned' : 'pending',
            'priority' => in_array($emergency->incident_type, ['accident', 'tow_needed']) ? 'urgent' : 'high',
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'geo_zone_id' => $geoZoneId,
            'notes' => $emergency->incident_description,
            'metadata' => [
                'source' => 'public_roadside_sos',
                'emergency_id' => $emergency->id,
                'incident_type' => $emergency->incident_type,
                'location' => [
                    'lat' => $emergency->lat,
                    'lng' => $emergency->lng,
                ],
            ],
            'location' => [
                'lat' => $emergency->lat,
                'lng' => $emergency->lng,
            ],
        ]);

        // Связать Emergency с Order
        $emergency->order_id = $order->id;

        // Ensure tracking token exists
        if (empty($emergency->tracking_token)) {
            $emergency->generateTrackingToken();
        } else {
            $emergency->save();
        }

        // Создать OrderItem
        OrderItem::create([
            'order_id' => $order->id,
            'service_type_id' => $serviceType->id,
            'name' => 'Roadside Assistance - '.ucfirst(str_replace('_', ' ', $emergency->incident_type)),
            'description' => $emergency->incident_description,
            'quantity' => 1,
            'unit_price' => 0, // Цена будет рассчитана позже
            'total_price' => 0,
            'currency' => 'NOK',
            'metadata' => [
                'emergency_id' => $emergency->id,
                'incident_type' => $emergency->incident_type,
            ],
        ]);

        return $order;
    }
}
