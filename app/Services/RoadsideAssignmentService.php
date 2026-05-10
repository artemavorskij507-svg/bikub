<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use Illuminate\Support\Facades\Log;

class RoadsideAssignmentService
{
    /**
     * Assign a helper or partner to the emergency.
     */
    public function assign(RoadsideEmergency $emergency): void
    {
        $softTasks = ['jump_start', 'fuel', 'flat_tire', 'locked_keys', 'engine_no_start'];
        $hardTasks = ['tow_needed', 'accident'];

        $incidentType = $emergency->incident_type;

        if (in_array($incidentType, $softTasks)) {
            // Мягкая задача - ищем Road Helper
            $this->assignHelper($emergency);
        } elseif (in_array($incidentType, $hardTasks)) {
            // Тяжелая задача - ищем Partner
            $this->assignPartner($emergency);
        }
    }

    /**
     * Assign a road helper to the emergency.
     */
    protected function assignHelper(RoadsideEmergency $emergency): void
    {
        if (! $emergency->lat || ! $emergency->lng) {
            Log::warning('Emergency has no coordinates for helper assignment', [
                'emergency_id' => $emergency->id,
            ]);

            return;
        }

        $helper = RoadHelperProfile::where('current_status', 'idle')
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->get()
            ->map(function ($h) use ($emergency) {
                $h->distance = $this->calculateDistance(
                    $emergency->lat,
                    $emergency->lng,
                    $h->location_lat,
                    $h->location_lng
                );

                return $h;
            })
            ->sortBy('distance')
            ->first();

        if ($helper) {
            $emergency->update([
                'road_helper_id' => $helper->id,
                'status' => 'assigned',
            ]);

            $helper->update(['current_status' => 'on_route']);

            Log::info('Road Helper assigned to emergency', [
                'emergency_id' => $emergency->id,
                'helper_id' => $helper->id,
            ]);
        } else {
            Log::warning('No available Road Helper found for emergency', [
                'emergency_id' => $emergency->id,
                'incident_type' => $emergency->incident_type,
            ]);
        }
    }

    /**
     * Assign a partner to the emergency.
     */
    protected function assignPartner(RoadsideEmergency $emergency): void
    {
        if (! $emergency->lat || ! $emergency->lng) {
            Log::warning('Emergency has no coordinates for partner assignment', [
                'emergency_id' => $emergency->id,
            ]);

            return;
        }

        $partners = Partner::where('type', Partner::TYPE_TOWING_SERVICE)
            ->where('active', true)
            ->where('is_available', true)
            ->get();

        $partner = $partners->map(function ($p) use ($emergency) {
            // Если у партнера есть координаты, вычисляем расстояние
            if (isset($p->location_lat) && isset($p->location_lng) && $p->location_lat && $p->location_lng) {
                $p->distance = $this->calculateDistance(
                    $emergency->lat,
                    $emergency->lng,
                    $p->location_lat,
                    $p->location_lng
                );
            } else {
                $p->distance = 999999; // Большое расстояние если координат нет
            }

            return $p;
        })
            ->sortBy('distance')
            ->first();

        if ($partner) {
            $emergency->update([
                'resolved_by_partner_id' => $partner->id,
                'status' => 'assigned',
            ]);

            Log::info('Partner assigned to emergency', [
                'emergency_id' => $emergency->id,
                'partner_id' => $partner->id,
            ]);
        } else {
            Log::warning('No available Partner found for emergency', [
                'emergency_id' => $emergency->id,
                'incident_type' => $emergency->incident_type,
            ]);
        }
    }

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in kilometers.
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
