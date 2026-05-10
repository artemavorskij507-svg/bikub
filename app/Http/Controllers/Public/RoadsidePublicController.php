<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoadsideHelpRequest;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\RoadsideOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoadsidePublicController extends Controller
{
    protected $orderService;

    public function __construct(RoadsideOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Show the roadside help form.
     */
    public function showForm()
    {
        $user = auth()->user();
        $userData = $user ? [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ] : [];

        return view('public.roadside-help', compact('userData'));
    }

    /**
     * Submit roadside help request.
     */
    public function submit(RoadsideHelpRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 1. Определить/создать пользователя
            $user = auth()->user();

            if (! $user) {
                // Создаём lightweight пользователя
                $user = User::firstOrCreate(
                    ['phone' => $data['phone']],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'] ?? ('roadside_'.time().'_'.rand(1000, 9999).'@temp.local'),
                        'password' => bcrypt(Str::random(10)),
                        'is_active' => true,
                    ]
                );
            } else {
                // Обновляем данные пользователя, если они изменились
                $user->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? $user->email,
                ]);
            }

            // 2. Определить тип услуги
            $serviceTypeCode = $data['service_type'];
            $serviceType = ServiceType::where('code', $serviceTypeCode)->first();

            if (! $serviceType) {
                throw new \Exception("Service type {$serviceTypeCode} not found. Please run RoadsideServiceTypesSeeder.");
            }

            // 3. Обработка vehicle_inspection отдельно
            if ($serviceTypeCode === 'vehicle_inspection') {
                return $this->handleInspectionRequest($user, $data, $serviceType);
            }

            // 4. Определить incident_type для RoadsideEmergency
            $incidentType = $this->mapServiceTypeToIncidentType($serviceTypeCode, $data['problem_type'] ?? null);

            // 5. Определить geo zone
            $geoZone = null;
            if (isset($data['location_lat']) && isset($data['location_lng'])) {
                $geoZone = $this->findGeoZoneByCoordinates($data['location_lat'], $data['location_lng']);
            }

            // 6. Создать RoadsideEmergency
            $emergency = RoadsideEmergency::create([
                'customer_id' => $user->id,
                'incident_type' => $incidentType,
                'incident_description' => $data['notes'] ?? null,
                'lat' => $data['location_lat'] ?? null,
                'lng' => $data['location_lng'] ?? null,
                'status' => 'new',
                'metadata' => [
                    'source' => 'public_help_form',
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'vehicle_make' => $data['vehicle_make'] ?? null,
                    'vehicle_model' => $data['vehicle_model'] ?? null,
                    'vehicle_plate' => $data['vehicle_plate'] ?? null,
                    'location_text' => $data['location_address'] ?? null,
                    'destination_address' => $data['destination_address'] ?? null,
                    'problem_type' => $data['problem_type'] ?? null,
                ],
            ]);

            // 7. Generate tracking token
            $emergency->generateTrackingToken();

            // 8. Create Order from Emergency
            $order = $this->orderService->createOrderFromEmergency($emergency);

            // 9. Update Order metadata with form data
            $order->metadata = array_merge($order->metadata ?? [], [
                'source' => 'public_help_form',
                'form_data' => $data,
            ]);
            $order->save();

            Log::info('Roadside help request submitted', [
                'emergency_id' => $emergency->id,
                'order_id' => $order->id,
                'user_id' => $user->id,
                'service_type' => $serviceTypeCode,
            ]);

            DB::commit();

            return redirect()
                ->route('roadside.help.success', ['emergency' => $emergency->id])
                ->with('status', 'Ваша заявка принята! Мы свяжемся с вами в ближайшее время.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to submit roadside help request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось отправить заявку. Пожалуйста, попробуйте еще раз или свяжитесь с нами по телефону.']);
        }
    }

    /**
     * Show success page after submission.
     */
    public function success($emergency)
    {
        // Можемо отримати як RoadsideEmergency, так і VehicleInspectionRequest
        $roadsideEmergency = \App\Models\RoadsideEmergency::find($emergency);
        $inspectionRequest = null;

        if (! $roadsideEmergency) {
            $inspectionRequest = \App\Models\VehicleInspectionRequest::find($emergency);
            if ($inspectionRequest) {
                $inspectionRequest->load(['order', 'customer']);

                return view('public.roadside-help-success', [
                    'emergency' => $inspectionRequest,
                    'order' => $inspectionRequest->order,
                    'is_inspection' => true,
                ]);
            }
        }

        if ($roadsideEmergency) {
            $roadsideEmergency->load(['order', 'customer']);

            return view('public.roadside-help-success', [
                'emergency' => $roadsideEmergency,
                'order' => $roadsideEmergency->order,
                'is_inspection' => false,
            ]);
        }

        abort(404, 'Заявка не найдена');
    }

    /**
     * Map service type to incident type.
     */
    protected function mapServiceTypeToIncidentType(string $serviceType, ?string $problemType): ?string
    {
        if ($serviceType === 'vehicle_transport') {
            return 'tow_needed';
        }

        if ($serviceType === 'vehicle_inspection') {
            return null; // VehicleInspectionRequest будет создан отдельно
        }

        // Для roadside_assistance определяем по problem_type
        if ($problemType) {
            $problemTypeLower = strtolower($problemType);

            if (str_contains($problemTypeLower, 'аккумулятор') || str_contains($problemTypeLower, 'прикурить') || str_contains($problemTypeLower, 'батарея')) {
                return 'jump_start';
            }
            if (str_contains($problemTypeLower, 'колесо') || str_contains($problemTypeLower, 'прокол') || str_contains($problemTypeLower, 'шина')) {
                return 'flat_tire';
            }
            if (str_contains($problemTypeLower, 'топливо') || str_contains($problemTypeLower, 'бензин') || str_contains($problemTypeLower, 'дизель')) {
                return 'fuel';
            }
            if (str_contains($problemTypeLower, 'ключ') || str_contains($problemTypeLower, 'замок')) {
                return 'locked_keys';
            }
            if (str_contains($problemTypeLower, 'не заводится') || str_contains($problemTypeLower, 'не запускается')) {
                return 'engine_no_start';
            }
            if (str_contains($problemTypeLower, 'авария') || str_contains($problemTypeLower, 'дтп')) {
                return 'accident';
            }
        }

        return 'engine_no_start'; // Default
    }

    /**
     * Handle vehicle inspection request.
     */
    protected function handleInspectionRequest(User $user, array $data, ServiceType $serviceType)
    {
        // Get first active preset as default
        $preset = \App\Models\VehicleInspectionPreset::where('is_active', true)->first();

        $inspectionRequest = \App\Models\VehicleInspectionRequest::create([
            'customer_id' => $user->id,
            'preset_id' => $preset?->id,
            'seller_name' => $data['name'],
            'seller_phone' => $data['phone'],
            'vehicle_make' => $data['vehicle_make'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'address' => $data['location_address'] ?? null,
            'status' => 'pending',
            'metadata' => [
                'source' => 'public_help_form',
                'email' => $data['email'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        ]);

        // Create Order for inspection
        $order = $this->orderService->createOrderFromInspectionRequest($inspectionRequest);

        Log::info('Vehicle inspection request submitted', [
            'inspection_request_id' => $inspectionRequest->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
        ]);

        DB::commit();

        return redirect()
            ->route('roadside.help.success', ['emergency' => $inspectionRequest->id])
            ->with('status', 'Ваша заявка на осмотр принята! Мы свяжемся с вами в ближайшее время.');
    }

    /**
     * Find geo zone by coordinates.
     */
    protected function findGeoZoneByCoordinates(float $lat, float $lng): ?GeoZone
    {
        // Простая логика: найти первую активную зону
        // В будущем можно добавить проверку по полигонам
        return GeoZone::where('is_active', true)->first();
    }
}
