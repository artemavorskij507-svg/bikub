<?php

namespace App\Observers;

use App\Models\GeoZone;
use App\Models\Moving\MovingOrder;
use App\Models\Task;
use Illuminate\Support\Carbon;

class MovingOrderObserver
{
    /**
     * Handle the MovingOrder "created" event.
     */
    public function created(MovingOrder $movingOrder): void
    {
        // Calculate total volume and weight if not set
        if (! $movingOrder->total_volume) {
            $movingOrder->total_volume = $movingOrder->calculateTotalVolume();
        }
        if (! $movingOrder->total_weight) {
            $movingOrder->total_weight = $movingOrder->calculateTotalWeight();
        }

        // Calculate estimated price
        if (! $movingOrder->estimated_price) {
            $movingOrder->estimated_price = $movingOrder->calculateTotalPrice();
        }

        $movingOrder->saveQuietly();

        // Create tasks for related services
        $this->createRelatedTasks($movingOrder);
    }

    /**
     * Handle the MovingOrder "updated" event.
     */
    public function updated(MovingOrder $movingOrder): void
    {
        // If status changed to confirmed, try to assign team
        if ($movingOrder->wasChanged('status') && $movingOrder->status === 'confirmed') {
            $this->assignTeam($movingOrder);
        }

        // If status changed to completed, update related tasks
        if ($movingOrder->wasChanged('status') && $movingOrder->status === 'completed') {
            $this->completeRelatedTasks($movingOrder);
        }
    }

    /**
     * Create related tasks for assembly and disposal services.
     */
    protected function createRelatedTasks(MovingOrder $movingOrder): void
    {
        $services = $movingOrder->services ?? [];
        $order = $movingOrder->order;

        // Find geo zone for destination
        $toGeoZone = $this->findGeoZone(
            $movingOrder->to_address['lat'] ?? 0,
            $movingOrder->to_address['lng'] ?? 0
        );

        $scheduledAt = $movingOrder->scheduled_at ?? now()->addDay();
        try {
            $baseWindowStart = is_string($scheduledAt)
                ? Carbon::parse($scheduledAt)
                : ($scheduledAt instanceof \Carbon\Carbon
                    ? $scheduledAt
                    : null);
            if (! $baseWindowStart) {
                \Log::warning('Invalid scheduled_at in MovingOrderObserver', [
                    'order_id' => $movingOrder->id,
                    'scheduled_at' => $scheduledAt,
                ]);

                return;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to parse scheduled_at in MovingOrderObserver', [
                'order_id' => $movingOrder->id,
                'scheduled_at' => $scheduledAt,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        // Create assembly task if assembly service is enabled
        if (($services['assembly'] ?? false) || ($services['disassembly'] ?? false)) {
            $assemblyTask = Task::create([
                'order_id' => $order?->id,
                'type' => 'furniture_assembly',
                'status' => 'queued',
                'priority' => 'normal',
                'sequence_index' => 1,
                'zone_id' => $toGeoZone?->id,
                'address_text' => $this->formatAddress($movingOrder->to_address),
                'lat' => $movingOrder->to_address['lat'] ?? null,
                'lng' => $movingOrder->to_address['lng'] ?? null,
                'window_start' => $baseWindowStart->copy()->addHours(2),
                'window_end' => $baseWindowStart->copy()->addHours(4),
                'expected_duration_min' => 120,
                'requirements' => [
                    'service' => 'furniture_assembly',
                    'moving_order_id' => $movingOrder->id,
                    'items_requiring_assembly' => $movingOrder->items()
                        ->where('requires_assembly', true)
                        ->pluck('name')
                        ->toArray(),
                ],
                'instructions' => 'Збірка меблів після переїзду. Замовлення переїзду #'.$movingOrder->id,
                'proof_required' => true,
                'meta' => [
                    'related_moving_order_id' => $movingOrder->id,
                    'related_type' => 'moving_order',
                ],
            ]);

            // Link task to moving order
            $movingOrder->relatedTasks()->attach($assemblyTask->id, [
                'task_type' => 'assembly',
            ]);
        }

        // Create disposal task if disposal service is enabled
        if ($services['disposal'] ?? false) {
            $disposalVolume = $movingOrder->calculateDisposalVolume();

            if ($disposalVolume > 0) {
                $disposalTask = Task::create([
                    'order_id' => $order?->id,
                    'type' => 'furniture_disposal',
                    'status' => 'queued',
                    'priority' => 'normal',
                    'sequence_index' => 2,
                    'zone_id' => $toGeoZone?->id,
                    'address_text' => $this->formatAddress($movingOrder->from_address),
                    'lat' => $movingOrder->from_address['lat'] ?? null,
                    'lng' => $movingOrder->from_address['lng'] ?? null,
                    'window_start' => $baseWindowStart->copy()->addHours(4),
                    'window_end' => $baseWindowStart->copy()->addHours(6),
                    'expected_duration_min' => 60,
                    'requirements' => [
                        'service' => 'furniture_disposal',
                        'moving_order_id' => $movingOrder->id,
                        'volume_m3' => $disposalVolume,
                    ],
                    'instructions' => 'Вивезення старих меблів. Замовлення переїзду #'.$movingOrder->id,
                    'proof_required' => true,
                    'meta' => [
                        'related_moving_order_id' => $movingOrder->id,
                        'related_type' => 'moving_order',
                        'disposal_volume' => $disposalVolume,
                    ],
                ]);

                // Link task to moving order
                $movingOrder->relatedTasks()->attach($disposalTask->id, [
                    'task_type' => 'disposal',
                ]);
            }
        }
    }

    /**
     * Assign optimal team to moving order.
     */
    protected function assignTeam(MovingOrder $movingOrder): void
    {
        if ($movingOrder->executor_team_id) {
            return; // Already assigned
        }

        $team = \App\Models\Moving\Team::findOptimalTeam($movingOrder);

        if ($team) {
            $movingOrder->executor_team_id = $team->id;
            $movingOrder->saveQuietly();
        }
    }

    /**
     * Complete related tasks when moving order is completed.
     */
    protected function completeRelatedTasks(MovingOrder $movingOrder): void
    {
        $movingOrder->relatedTasks()
            ->where('status', '!=', 'completed')
            ->update([
                'status' => 'completed',
            ]);
    }

    /**
     * Find geo zone for location.
     */
    protected function findGeoZone(float $latitude, float $longitude): ?GeoZone
    {
        if (! $latitude || ! $longitude) {
            return null;
        }

        return GeoZone::where('is_active', true)
            ->get()
            ->first(function ($zone) use ($latitude, $longitude) {
                return $zone->containsPoint($latitude, $longitude);
            });
    }

    /**
     * Format address array to string.
     */
    protected function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['street'] ?? null,
            $address['building_type'] ?? null,
            isset($address['floor']) ? 'Поверх '.$address['floor'] : null,
        ]);

        return implode(', ', $parts);
    }
}
