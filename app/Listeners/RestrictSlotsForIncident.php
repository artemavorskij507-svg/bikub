<?php

namespace App\Listeners;

use App\Events\TrafficIncidentUpdated;
use App\Models\GeoZone;
use App\Models\ScheduleSlot;
use Illuminate\Support\Facades\Log;

class RestrictSlotsForIncident
{
    public function handle(TrafficIncidentUpdated $event): void
    {
        if ($event->incident->severity !== 'severe') {
            return;
        }

        $zoneIds = $event->affectedZones ?: $this->findAffectedZones($event->incident);

        foreach ($zoneIds as $zoneId) {
            $slots = ScheduleSlot::where('zone_id', $zoneId)
                ->where('is_active', true)
                ->where('is_closed', false)
                ->get();

            foreach ($slots as $slot) {
                $slot->is_closed = true;
                $slot->override_reason = "Severe incident: {$event->incident->title}";
                $slot->save();
            }

            Log::info('Slots restricted due to severe incident', [
                'incident_id' => $event->incident->id,
                'zone_id' => $zoneId,
                'slots_count' => $slots->count(),
            ]);
        }
    }

    protected function findAffectedZones($incident): array
    {
        if (! $incident->lat || ! $incident->lng) {
            return [];
        }

        return GeoZone::query()
            ->whereRaw(
                'ST_DWithin(
                    ST_MakePoint(center_longitude, center_latitude)::geography,
                    ST_MakePoint(?, ?)::geography,
                    radius_meters
                )',
                [$incident->lng, $incident->lat]
            )
            ->pluck('id')
            ->toArray();
    }
}
