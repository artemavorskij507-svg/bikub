<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Enums\JobKind;
use App\Domain\Operations\Enums\ServiceDomain;
use App\Domain\Operations\Enums\ServiceJobStatus;
use App\Domain\Operations\Models\ServiceJob;
use App\Events\ServiceJobCreated;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class NormalizeOrderToServiceJobAction
{
    public function execute(Order $order): ServiceJob
    {
        $serviceType = (string) ($order->service_type ?? 'delivery');

        $serviceDomain = match ($serviceType) {
            'delivery' => ServiceDomain::DELIVERY->value,
            'handyman', 'repair' => ServiceDomain::HANDYMAN->value,
            'moving' => ServiceDomain::MOVING->value,
            'roadside' => ServiceDomain::ROADSIDE->value,
            'social_care', 'care' => ServiceDomain::SOCIAL_CARE->value,
            default => ServiceDomain::DELIVERY->value,
        };

        $jobKind = match ($serviceType) {
            'delivery' => JobKind::SHIPMENT->value,
            'handyman', 'repair' => JobKind::VISIT->value,
            'moving' => JobKind::CREW_MOVE->value,
            'roadside' => JobKind::EMERGENCY->value,
            default => JobKind::VISIT->value,
        };

        $payload = [
            'organization_id' => $order->org_id ?? data_get($order->metadata, 'organization_id'),
            'tenant_id' => data_get($order->metadata, 'tenant_id'),
            'source_type' => 'order',
            'source_id' => $order->id,
            'order_id' => $order->id,
            'service_domain' => $serviceDomain,
            'job_type' => $jobKind,
            'job_kind' => $jobKind,
            'status' => ServiceJobStatus::PENDING_DISPATCH->value,
            'priority' => $order->priority ?? 'normal',
            'customer_id' => $order->user_id,
            'geo_zone_id' => $order->geo_zone_id,
            'schedule_slot_id' => $order->schedule_slot_id,
            'pickup_lat' => data_get($order->location, 'pickup.lat'),
            'pickup_lng' => data_get($order->location, 'pickup.lng'),
            'dropoff_lat' => data_get($order->location, 'dropoff.lat'),
            'dropoff_lng' => data_get($order->location, 'dropoff.lng'),
            'service_lat' => data_get($order->location, 'service.lat'),
            'service_lng' => data_get($order->location, 'service.lng'),
            'time_window_start' => $order->scheduled_at ?? data_get($order->metadata, 'scheduled_from'),
            'time_window_end' => data_get($order->metadata, 'scheduled_to'),
            'service_duration_minutes' => data_get($order->metadata, 'estimated_duration_minutes', 30),
            'required_skills' => data_get($order->metadata, 'required_skills', []),
            'required_equipment' => data_get($order->metadata, 'required_equipment', []),
            'required_capacity' => data_get($order->metadata, 'required_capacity', []),
            'price_snapshot' => [
                'amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
            'metadata' => array_merge($order->metadata ?? [], [
                'order_number' => $order->order_number,
            ]),
            'created_by' => $order->user_id,
            'updated_by' => $order->user_id,
        ];

        $columns = Schema::hasTable('service_jobs')
            ? Schema::getColumnListing('service_jobs')
            : [];

        $columnSet = array_flip($columns);

        if (! isset($columnSet['geo_zone_id']) && isset($columnSet['zone_id'])) {
            $payload['zone_id'] = $order->geo_zone_id;
        }

        $payload = array_intersect_key($payload, $columnSet);

        $job = ServiceJob::query()->updateOrCreate(
            ['source_type' => 'order', 'source_id' => $order->id],
            $payload
        );

        event(new ServiceJobCreated($job));

        return $job->fresh();
    }
}
