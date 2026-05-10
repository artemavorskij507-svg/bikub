<?php

namespace App\Http\Controllers\Public\Handyman;

use App\Enums\ServiceType;
use App\Events\HandymanOrderRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\Handyman\HandymanBookingRequest;
use App\Http\Requests\Public\Handyman\HandymanCustomRequest;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use App\Services\Handyman\HandymanAssignmentService;
use App\Services\Notifications\NotificationFeedService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HandymanBookingController extends Controller
{
    public function __construct(
        protected NotificationFeedService $feedService
    ) {}

    public function store(HandymanBookingRequest $request, string $slug)
    {
        $user = $request->user();
        $handymanService = HandymanService::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($request, $user, $handymanService) {
            // 1. Посчитать примерную цену
            $estimatedPriceMinor = $this->calculateEstimatedPriceMinor($handymanService, $request->validated());

            // 2. Определить тип услуги
            $serviceType = $handymanService->pricing_mode === HandymanService::PRICING_FIXED
                ? ServiceType::HANDYMAN_FIXED
                : ServiceType::HANDYMAN_HOURLY;

            // 3. Парсить желаемое время
            $desiredStartAt = null;
            $desiredFinishAt = null;
            if ($request->has('desired_date') && $request->has('desired_time_from') && $request->has('desired_time_to')) {
                $date = Carbon::parse($request->input('desired_date'));
                $timeFrom = Carbon::parse($request->input('desired_time_from'));
                $timeTo = Carbon::parse($request->input('desired_time_to'));
                $desiredStartAt = $date->copy()->setTime($timeFrom->hour, $timeFrom->minute);
                $desiredFinishAt = $date->copy()->setTime($timeTo->hour, $timeTo->minute);
            }

            // 4. Создать Order
            $order = Order::create([
                'user_id' => $user->id,
                'service_type' => $serviceType->value,
                'status' => 'pending_payment',
                'estimated_total' => $estimatedPriceMinor, // integer (minor units)
                'currency' => 'NOK',
                'payment_status' => 'pending',
            ]);

            // 5. Создать HandymanOrderDetails
            $details = HandymanOrderDetails::create([
                'order_id' => $order->id,
                'handyman_service_id' => $handymanService->id,
                'is_custom_request' => false,
                'description' => $request->input('description'),
                'context_notes' => $request->input('context_notes'),
                'needs_materials_purchase' => $request->boolean('needs_materials_purchase'),
                'materials_notes' => $request->input('materials_notes'),
                'expected_duration_minutes' => $request->input('expected_duration_minutes'),
                'address_line' => $request->input('address_line'),
                'postal_code' => $request->input('postal_code'),
                'city' => $request->input('city'),
                'estimated_price_minor' => $estimatedPriceMinor,
                'desired_start_at' => $desiredStartAt,
                'desired_finish_at' => $desiredFinishAt,
            ]);

            event(new HandymanOrderRequested($order));

            // 6. Отправить уведомление
            $this->feedService->push(
                $user,
                'handyman.order_created',
                'handyman',
                'Создан заказ "Мастер на час"',
                'Мы получили ваш запрос, скоро подтвердим стоимость и время.',
                $order,
                ['order_id' => $order->id]
            );

            // 7. Подобрать кандидатов (после сохранения заказа)
            try {
                /** @var HandymanAssignmentService $assignmentService */
                $assignmentService = app(HandymanAssignmentService::class);
                $assignmentService->proposeAssignmentsForOrder($order);
                // TODO: уведомить диспетчера и/или отправить push кандидатам
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем создание заказа
                \Log::error('Failed to propose handyman assignments', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 8. Перенаправить на оплату или страницу подтверждения заказа
            return redirect()
                ->route('account.orders.show', $order)
                ->with('status', 'Заказ на услугу "Мастер на час" успешно создан, завершите оплату.');
        });
    }

    public function storeCustom(HandymanCustomRequest $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            // тут нет конкретной услуги, всё на диспетчера
            $estimatedPriceMinor = null; // можно попытаться оценить потом вручную

            // Парсить желаемое время (опционально)
            $desiredStartAt = null;
            $desiredFinishAt = null;
            if ($request->filled('desired_date') && $request->filled('desired_time_from') && $request->filled('desired_time_to')) {
                $date = Carbon::parse($request->input('desired_date'));
                $timeFrom = Carbon::parse($request->input('desired_time_from'));
                $timeTo = Carbon::parse($request->input('desired_time_to'));
                $desiredStartAt = $date->copy()->setTime($timeFrom->hour, $timeFrom->minute);
                $desiredFinishAt = $date->copy()->setTime($timeTo->hour, $timeTo->minute);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'service_type' => ServiceType::HANDYMAN_HOURLY->value,
                'status' => 'pending_review', // сначала оценивает диспетчер
                'estimated_total' => $estimatedPriceMinor,
                'currency' => 'NOK',
                'payment_status' => 'pending',
            ]);

            $details = HandymanOrderDetails::create([
                'order_id' => $order->id,
                'handyman_service_id' => null,
                'is_custom_request' => true,
                'description' => $request->input('description'),
                'context_notes' => $request->input('context_notes'),
                'needs_materials_purchase' => $request->boolean('needs_materials_purchase'),
                'materials_notes' => $request->input('materials_notes'),
                'expected_duration_minutes' => $request->input('expected_duration_minutes'),
                'address_line' => $request->input('address_line'),
                'postal_code' => $request->input('postal_code'),
                'city' => $request->input('city'),
                'desired_start_at' => $desiredStartAt,
                'desired_finish_at' => $desiredFinishAt,
            ]);

            event(new HandymanOrderRequested($order));

            // Отправить уведомление
            $this->feedService->push(
                $user,
                'handyman.custom_request_created',
                'handyman',
                'Создана заявка "Мастер на час"',
                'Мы получили ваш запрос, диспетчер уточнит детали и подтвердит стоимость.',
                $order,
                ['order_id' => $order->id]
            );

            // Подобрать кандидатов (после сохранения заказа)
            try {
                /** @var HandymanAssignmentService $assignmentService */
                $assignmentService = app(HandymanAssignmentService::class);
                $assignmentService->proposeAssignmentsForOrder($order);
            } catch (\Exception $e) {
                \Log::error('Failed to propose handyman assignments for custom request', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // TODO: уведомить диспетчера, чтобы он оценил заявку и выставил цену

            return redirect()
                ->route('account.orders.show', $order)
                ->with('status', 'Заявка отправлена, мы уточним детали и подтвердим стоимость.');
        });
    }

    private function calculateEstimatedPriceMinor(HandymanService $service, array $data): int
    {
        // Базовая логика:
        // - если FIXED: вернуть base_rate_minor
        // - если HOURLY: base_rate_minor * (expected_duration_minutes / 60)
        // - плюс возможные коэффициенты (этаж, срочность и т.п.) — TODO.

        $base = $service->base_rate_minor ?? 0;

        if ($service->pricing_mode === HandymanService::PRICING_FIXED) {
            return $base;
        }

        $minutes = $data['expected_duration_minutes'] ?? $service->estimated_duration_minutes ?? 60;
        $hours = max($minutes / 60, 1);

        return (int) round($base * $hours);
    }
}
