<?php

namespace App\Observers;

use App\Models\Claim;
use App\Services\Claims\ClaimSlaService;
use App\Services\Handyman\HandymanKpiService;

class ClaimObserver
{
    public function created(Claim $claim): void
    {
        // Безопасно инициализируем SLA, не ломая создание претензии при ошибках.
        try {
            app(ClaimSlaService::class)->setInitialSla($claim);
        } catch (\Throwable $e) {
            \Log::error('Failed to initialize SLA for claim', [
                'claim_id' => $claim->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // Уведомление систем (n8n, OPS и т.д.) также не должно ломать UI
        try {
            event(new \App\Events\ClaimCreated($claim));
        } catch (\Throwable $e) {
            \Log::error('Failed to dispatch ClaimCreated event', [
                'claim_id' => $claim->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Claim $claim): void
    {
        // SLA breach обновление — не даём упасть UI при проблемах сервиса
        try {
            app(ClaimSlaService::class)->updateSlaBreaches($claim);
        } catch (\Throwable $e) {
            \Log::error('Failed to update SLA breaches for claim', [
                'claim_id' => $claim->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // KPI мастеров (Шаг 9) - через order и handymanAssignments
        try {
            if ($claim->order) {
                foreach ($claim->order->handymanAssignments ?? [] as $assignment) {
                    if ($assignment->executorProfile ?? null) {
                        app(HandymanKpiService::class)->recalculateForExecutor($assignment->executorProfile);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to recalculate handyman KPI for claim', [
                'claim_id' => $claim->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
