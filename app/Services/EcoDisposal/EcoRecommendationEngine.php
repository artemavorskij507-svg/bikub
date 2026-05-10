<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Services\EcoDisposal\Contracts\EcoRecommendationEngineInterface;
use App\Services\EcoDisposal\Contracts\EcoTimeslotDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcoRecommendationEngine implements EcoRecommendationEngineInterface
{
    public function recommendPartnerForOrder(Order $order): ?DisposalPartner
    {
        if (! $order->isEcoDisposal()) {
            return null;
        }

        $order->loadMissing('disposalDetails');
        /** @var DisposalOrderDetails|null $details */
        $details = $order->disposalDetails;
        if (! $details || ! is_array($details->items)) {
            return null;
        }

        // Собираем категории и пути утилизации
        $itemIds = collect($details->items)->pluck('disposal_item_id')->filter()->values();
        $items = DB::table('disposal_items')
            ->whereIn('id', $itemIds)
            ->get(['id', 'category', 'disposal_path']);

        if ($items->isEmpty()) {
            return null;
        }

        $categories = $items->pluck('category')->filter()->unique()->values()->all();
        $paths = $items->pluck('disposal_path')->filter()->all();
        $dominantPath = $this->dominantDisposalPath($paths);

        $partners = DisposalPartner::query()
            ->where('is_active', true)
            ->where(function ($q) use ($categories) {
                $q->whereJsonContains('accepted_categories', $categories[0] ?? null);
                // TODO: улучшить пересечение категорий с JSON accepted_categories (мульти-значения)
            })
            ->when($dominantPath === 'HAZARDOUS', function ($q) {
                $q->where('type', 'HAZARDOUS_PROCESSOR');
            })
            ->orderBy('name')
            ->get();

        if ($partners->isEmpty()) {
            Log::info('EcoRecommendation: no partner found', ['order_id' => $order->id]);

            return null;
        }

        // TODO: учитывать расстояние до zone / координаты
        $selected = $partners->first();

        Log::info('EcoRecommendation: partner suggested', [
            'order_id' => $order->id,
            'partner_id' => $selected->id,
            'categories' => $categories,
            'dominant_path' => $dominantPath,
        ]);

        return $selected;
    }

    public function recommendTeamForOrder(Order $order): ?EcoTeam
    {
        if (! $order->isEcoDisposal()) {
            return null;
        }

        $order->loadMissing('disposalDetails');
        /** @var DisposalOrderDetails|null $details */
        $details = $order->disposalDetails;
        if (! $details) {
            return null;
        }

        $volume = (float) ($details->estimated_volume_m3 ?? 0);
        $weight = (float) ($details->estimated_weight_kg ?? 0);

        $teams = EcoTeam::query()
            ->where('is_active', true)
            ->where(function ($q) use ($volume) {
                $q->whereNull('vehicle_capacity_m3')
                    ->orWhere('vehicle_capacity_m3', '>=', $volume);
            })
            ->where(function ($q) use ($weight) {
                $q->whereNull('vehicle_max_weight_kg')
                    ->orWhere('vehicle_max_weight_kg', '>=', $weight);
            })
            ->orderBy('vehicle_capacity_m3') // от меньшей к большей
            ->get();

        if ($teams->isEmpty()) {
            Log::info('EcoRecommendation: no team found', ['order_id' => $order->id, 'volume' => $volume, 'weight' => $weight]);

            return null;
        }

        $selected = $teams->first();

        Log::info('EcoRecommendation: team suggested', [
            'order_id' => $order->id,
            'team_id' => $selected->id,
            'volume' => $volume,
            'weight' => $weight,
        ]);

        return $selected;
    }

    public function recommendTimeslotForOrder(Order $order): ?EcoTimeslotDto
    {
        // TODO: интеграция с модулем таймслотов / ScheduleSlotResource
        // Сейчас возвращаем null как заглушку
        return null;
    }

    protected function dominantDisposalPath(array $paths): ?string
    {
        if (empty($paths)) {
            return null;
        }
        $counts = array_count_values($paths);
        arsort($counts);

        return array_key_first($counts);
    }
}
