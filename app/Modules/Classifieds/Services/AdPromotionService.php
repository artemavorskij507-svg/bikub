<?php

namespace App\Modules\Classifieds\Services;

use App\Modules\Classifieds\Models\AdPayment;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Support\Facades\DB;

class AdPromotionService
{
    public function bump(ClassifiedAd $ad, float $amount = 10.0): void
    {
        DB::transaction(function () use ($ad, $amount) {
            $ad->update([
                'bumped_at' => now(),
                'published_at' => now(),
            ]);

            $this->recordPayment($ad, 'bump', $amount, [
                'duration_days' => 0,
            ]);
        });
    }

    public function highlight(ClassifiedAd $ad, int $days, float $amount): void
    {
        DB::transaction(function () use ($ad, $days, $amount) {
            $ad->update([
                'highlight_expires_at' => now()->addDays($days),
            ]);

            $this->recordPayment($ad, 'highlight', $amount, [
                'duration_days' => $days,
            ]);
        });
    }

    public function top(ClassifiedAd $ad, int $days, float $amount): void
    {
        DB::transaction(function () use ($ad, $days, $amount) {
            $ad->update([
                'top_expires_at' => now()->addDays($days),
            ]);

            $this->recordPayment($ad, 'top', $amount, [
                'duration_days' => $days,
            ]);
        });
    }

    public function vip(ClassifiedAd $ad, int $days, float $amount): void
    {
        DB::transaction(function () use ($ad, $days, $amount) {
            $ad->update([
                'vip_expires_at' => now()->addDays($days),
            ]);

            $this->recordPayment($ad, 'vip', $amount, [
                'duration_days' => $days,
            ]);
        });
    }

    protected function recordPayment(ClassifiedAd $ad, string $type, float $amount, array $meta): void
    {
        AdPayment::create([
            'ad_id' => $ad->id,
            'service_type' => $type,
            'amount' => $amount,
            'currency' => 'NOK',
            'meta' => $meta,
            // 'order_id'   => null, // можно привязать к реальному Order
        ]);
    }
}
