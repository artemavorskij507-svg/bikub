<?php

namespace App\Services\EcoDisposal;

use App\Models\EcoRecommendationLog;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class EcoRecommendationLogger
{
    public function logEngineOutput(Order $order, array $features, array $recommendations): void
    {
        try {
            EcoRecommendationLog::create([
                'order_id' => $order->id,
                'engine_version' => 'rule_v1',
                'features' => $features,
                'recommendations' => $recommendations,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log eco recommendation', ['order_id' => $order->id, 'err' => $e->getMessage()]);
        }
    }

    public function markAccepted(Order $order, bool $accepted): void
    {
        try {
            $log = EcoRecommendationLog::where('order_id', $order->id)
                ->latest('id')
                ->first();
            if ($log) {
                $log->accepted = $accepted;
                $log->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to mark eco recommendation accepted', ['order_id' => $order->id, 'err' => $e->getMessage()]);
        }
    }
}
