<?php

namespace App\Modules\Logistics\Listeners;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentModuleAssignment;
use App\Modules\AgencyAgents\Services\AgentEventBusService;
use App\Modules\Logistics\Events\CourierLocationUpdated;
use App\Modules\Logistics\Events\ShipmentLifecycleChanged;

class DispatchLogisticsAgents
{
    public function __construct(private readonly AgentEventBusService $eventBus)
    {
    }

    public function handle(object $event): void
    {
        if ($event instanceof ShipmentLifecycleChanged) {
            $this->handleShipmentLifecycleChanged($event);
            return;
        }

        if ($event instanceof CourierLocationUpdated) {
            $this->handleCourierLocationUpdated($event);
        }
    }

    private function handleShipmentLifecycleChanged(ShipmentLifecycleChanged $event): void
    {
        $payload = array_merge($event->payload, [
            'shipment_id' => $event->shipment->id,
            'shipment_number' => $event->shipment->shipment_number,
            'status' => $event->shipment->status,
            'assigned_personnel_id' => $event->shipment->assigned_personnel_id,
        ]);

        foreach (['logistics', 'orders', 'customers', 'warehouse', 'map'] as $moduleKey) {
            $logs = $this->eventBus->publish($moduleKey, $event->eventName, $payload, null, 'shipment-lifecycle');

            if ($moduleKey === 'orders' && $event->eventName === 'shipment.created') {
                $this->queueReactionTasks($moduleKey, 'Review new order dispatch', $event->shipment->shipment_number, $logs);
            }

            if (in_array($event->shipment->status, ['delayed', 'exception', 'cancelled'], true)) {
                $this->queueReactionTasks($moduleKey, 'Investigate delivery exception', $event->shipment->shipment_number, $logs);
            }
        }
    }

    private function handleCourierLocationUpdated(CourierLocationUpdated $event): void
    {
        $logs = $this->eventBus->publish('map', 'courier.location.updated', $event->payload, null, 'gps-update');
        $this->eventBus->publish('fleet', 'courier.location.updated', $event->payload, null, 'gps-update');

        if (($event->payload['status'] ?? null) === 'route_deviation') {
            $this->queueReactionTasks('map', 'Review route deviation', (string) ($event->payload['personnel_id'] ?? 'unknown'), $logs);
        }
    }

    private function queueReactionTasks(string $moduleKey, string $title, string $subject, array $logs): void
    {
        $assignments = AgentModuleAssignment::query()
            ->with('agent')
            ->where('module_key', $moduleKey)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->limit(3)
            ->get();

        foreach ($assignments as $assignment) {
            if (!$assignment->agent instanceof Agent) {
                continue;
            }

            $this->eventBus->createReactionTask(
                $assignment->agent,
                $title,
                sprintf('%s requires %s attention for %s.', ucfirst($moduleKey), strtolower($title), $subject),
                [
                    'category' => 'logistics',
                    'priority' => 'high',
                    'module_key' => $moduleKey,
                ]
            );
        }
    }
}

