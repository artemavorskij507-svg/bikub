<?php

namespace App\Observers;

use App\Models\RoadsideEmergency;
use App\Models\Task;
use App\Models\User;
use App\Notifications\RoadsideEmergencyStatusChanged;
use App\Notifications\RoadsideNewRequestNotification;
use Illuminate\Support\Facades\Log;

class RoadsideEmergencyObserver
{
    /**
     * Handle the RoadsideEmergency "creating" event.
     */
    public function creating(RoadsideEmergency $emergency): void
    {
        if (! $emergency->status) {
            $emergency->status = RoadsideEmergency::STATUS_NEW;
        }
    }

    /**
     * Handle the RoadsideEmergency "created" event.
     */
    public function created(RoadsideEmergency $emergency): void
    {
        Log::info('RoadsideEmergency created', [
            'id' => $emergency->id,
            'customer_id' => $emergency->customer_id,
            'incident_type' => $emergency->incident_type,
            'status' => $emergency->status,
        ]);

        // Уведомить операторов/диспетчеров о новой заявке
        if ($emergency->order) {
            $dispatchers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'dispatcher']);
            })->get();

            foreach ($dispatchers as $dispatcher) {
                $dispatcher->notify(new RoadsideNewRequestNotification($emergency, $emergency->order));
            }
        }
    }

    /**
     * Handle the RoadsideEmergency "updating" event.
     */
    public function updating(RoadsideEmergency $emergency): void
    {
        // Логируем назначение helper
        if ($emergency->isDirty('road_helper_id') && $emergency->road_helper_id) {
            Log::info('RoadsideEmergency helper assigned', [
                'emergency_id' => $emergency->id,
                'helper_id' => $emergency->road_helper_id,
                'previous_helper_id' => $emergency->getOriginal('road_helper_id'),
            ]);
        }

        // Логируем назначение партнера
        if ($emergency->isDirty('resolved_by_partner_id') && $emergency->resolved_by_partner_id) {
            Log::info('RoadsideEmergency partner assigned', [
                'emergency_id' => $emergency->id,
                'partner_id' => $emergency->resolved_by_partner_id,
                'previous_partner_id' => $emergency->getOriginal('resolved_by_partner_id'),
            ]);
        }
    }

    /**
     * Handle the RoadsideEmergency "updated" event.
     */
    public function updated(RoadsideEmergency $emergency): void
    {
        Log::info('RoadsideEmergency updated', [
            'id' => $emergency->id,
            'status' => $emergency->status,
        ]);

        // Синхронізуємо статус Order, якщо змінився статус RoadsideEmergency
        if ($emergency->isDirty('status')) {
            $oldStatus = $emergency->getOriginal('status');

            $emergency->syncOrderStatus();

            // Зберігаємо timestamp в metadata для таймлайну
            $metadata = $emergency->metadata ?? [];
            $statusTimestamps = [
                'assigned' => 'assigned_at',
                'on_route' => 'en_route_at',
                'in_progress' => 'on_site_at',
                'completed' => 'completed_at',
            ];

            if (isset($statusTimestamps[$emergency->status])) {
                $metadata[$statusTimestamps[$emergency->status]] = now()->toIso8601String();
                $emergency->metadata = $metadata;
                $emergency->saveQuietly(); // Без повторного виклику Observer
            }

            // Отправить уведомления о смене статуса
            $this->notifyStatusChanged($emergency, $oldStatus);

            // Синхронизация с Task для Wallet
            if ($emergency->isCompleted()) {
                $this->syncTaskForCompletedJob($emergency);
            }

            if ($emergency->isCancelled() || $emergency->isRejected()) {
                $this->cancelTaskForJob($emergency);
            }
        }
    }

    /**
     * Notify users about status change.
     */
    protected function notifyStatusChanged(RoadsideEmergency $emergency, string $oldStatus): void
    {
        // Уведомить операторов/диспетчеров
        $dispatchers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'operator', 'dispatcher']);
        })->get();

        foreach ($dispatchers as $dispatcher) {
            $dispatcher->notify(new RoadsideEmergencyStatusChanged($emergency, $oldStatus));
        }

        // Уведомить исполнителя, если он назначен
        if ($emergency->order && $emergency->order->assigned_to) {
            $executor = User::find($emergency->order->assigned_to);
            if ($executor) {
                $executor->notify(new RoadsideEmergencyStatusChanged($emergency, $oldStatus));
            }
        }

        // Уведомить helper, если он назначен
        if ($emergency->helper && $emergency->helper->user) {
            $emergency->helper->user->notify(new RoadsideEmergencyStatusChanged($emergency, $oldStatus));
        }

        // Уведомить клиента, если статус критичный (completed, cancelled, failed)
        if (in_array($emergency->status, [
            RoadsideEmergency::STATUS_COMPLETED,
            RoadsideEmergency::STATUS_CANCELLED,
            RoadsideEmergency::STATUS_FAILED,
        ])) {
            if ($emergency->customer) {
                $emergency->customer->notify(new RoadsideEmergencyStatusChanged($emergency, $oldStatus));
            }
        }
    }

    /**
     * Sync Task for completed RoadsideEmergency.
     */
    protected function syncTaskForCompletedJob(RoadsideEmergency $emergency): void
    {
        $assignedUserId = $emergency->getAssignedUserId();

        if (! $assignedUserId || ! $emergency->order_id) {
            return;
        }

        $executorPayout = $emergency->getExecutorPayout();

        if ($executorPayout <= 0) {
            // TODO: в будущем подключить реальный RoadsidePricingService
            Log::info('RoadsideEmergency completed but executor_payout is 0', [
                'emergency_id' => $emergency->id,
                'order_id' => $emergency->order_id,
            ]);

            return;
        }

        // Синхронизируем payout в Order metadata
        $emergency->syncExecutorPayoutToOrder();

        // Создаем или обновляем Task
        // Примечание: Task использует assignee_id (Employee), но для Roadside можем использовать через Order->assigned_to
        // Если нужен user_id напрямую, можно добавить его в Task или использовать через Employee
        $completedAt = null;
        if (isset($emergency->metadata['completed_at'])) {
            $completedAt = is_string($emergency->metadata['completed_at'])
                ? \Carbon\Carbon::parse($emergency->metadata['completed_at'])
                : $emergency->metadata['completed_at'];
        } else {
            $completedAt = $emergency->order?->completed_at ?? now();
        }

        Task::updateOrCreate(
            [
                'order_id' => $emergency->order_id,
                'type' => 'roadside_job',
            ],
            [
                'status' => 'completed',
                'payout_amount' => $executorPayout,
                'currency' => 'NOK',
                'completed_at' => $completedAt,
                'meta' => array_merge($emergency->metadata ?? [], [
                    'source' => 'roadside',
                    'roadside_emergency_id' => $emergency->id,
                    'incident_type' => $emergency->incident_type ?? null,
                    'executor_user_id' => $assignedUserId, // Сохраняем user_id в meta для Wallet
                ]),
            ]
        );

        Log::info('Task created/updated for completed RoadsideEmergency', [
            'emergency_id' => $emergency->id,
            'order_id' => $emergency->order_id,
            'user_id' => $assignedUserId,
            'payout_amount' => $executorPayout,
        ]);
    }

    /**
     * Cancel Task for cancelled/rejected RoadsideEmergency.
     */
    protected function cancelTaskForJob(RoadsideEmergency $emergency): void
    {
        if (! $emergency->order_id) {
            return;
        }

        Task::where('order_id', $emergency->order_id)
            ->where('type', 'roadside_job')
            ->update([
                'status' => 'cancelled',
            ]);

        Log::info('Task cancelled for RoadsideEmergency', [
            'emergency_id' => $emergency->id,
            'order_id' => $emergency->order_id,
            'status' => $emergency->status,
        ]);
    }
}
