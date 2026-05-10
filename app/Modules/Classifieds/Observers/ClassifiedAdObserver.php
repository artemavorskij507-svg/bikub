<?php

namespace App\Modules\Classifieds\Observers;

use App\Jobs\GeocodeClassifiedAdJob;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Services\AIModerationService;
use Illuminate\Support\Facades\Log;

class ClassifiedAdObserver
{
    /**
     * Синхронная AI‑премодерация перед сохранением.
     */
    public function saving(ClassifiedAd $ad): void
    {
        // Проверяем только при изменении текста и если объявление не в статусе rejected
        if (($ad->isDirty('title') || $ad->isDirty('description')) && $ad->status !== 'rejected') {
            try {
                $service = app(AIModerationService::class);
                $result = $service->analyze($ad);

                if (! $result['is_safe']) {
                    $ad->status = 'moderation';
                    $ad->moderation_reason = 'AI Flag: '.($result['reason'] ?? 'Potentially unsafe content');
                }
            } catch (\Throwable $e) {
                // Не блокируем сохранение при падении AI
                Log::warning('AI Observer skipped: '.$e->getMessage());
            }
        }
    }

    /**
     * Асинхронная геокодировка адреса после сохранения.
     */
    public function saved(ClassifiedAd $ad): void
    {
        if ($ad->wasChanged('address') && ! empty($ad->address) && ! $ad->hasLocation()) {
            GeocodeClassifiedAdJob::dispatch($ad->id);
        }
    }
}
