<?php

namespace App\Services\Operations;

use App\Models\HandymanAssignment;
use App\Models\Order;
use App\Models\Operations\ServiceJob;
use App\Models\Task;
use Carbon\Carbon;

class ServiceJobNormalizer
{
    public function normalizeFromOrder(Order $order, array $context = []): ServiceJob
    {
        $serviceDomain = $this->detectServiceDomain($order, $context);
        $jobType = $context['job_type'] ?? ($serviceDomain === 'delivery' ? 'pickup_dropoff' : 'on_site_visit');

        $existing = ServiceJob::where('source_type', 'order')
            ->where('source_id', $order->id)
            ->first();

        $payload = [
            'organization_id' => $order->org_id ?? data_get($order->metadata, 'organization_id'),
            'source_type' => 'order',
            'source_id' => $order->id,
            'order_id' => $order->id,
            'service_domain' => $serviceDomain,
            'job_type' => $jobType,
            'status' => $this->mapOrderStatus($order->status),
            'priority' => $order->priority ?? 'normal',
            'pickup_point' => data_get($order->location, 'pickup'),
            'dropoff_point' => data_get($order->location, 'dropoff'),
            'service_point' => data_get($order->location, 'service'),
            'time_window_start' => $order->scheduled_at,
            'time_window_end' => $this->deriveWindowEnd($order->scheduled_at),
            'service_duration_minutes' => data_get($context, 'service_duration_minutes', 45),
            'required_skills' => data_get($context, 'required_skills', $this->deriveSkills($serviceDomain)),
            'required_capacity' => data_get($context, 'required_capacity'),
            'required_equipment' => data_get($context, 'required_equipment'),
            'zone_id' => $order->geo_zone_id,
            'schedule_slot_id' => $order->schedule_slot_id,
            'sla_policy_id' => $order->sla_policy_id,
            'promised_sla_minutes' => data_get($context, 'promised_sla_minutes', 60),
            'metadata' => [
                'normalized_at' => now()->toIso8601String(),
                'source_status' => $order->status,
                'notes' => $order->notes,
            ],
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing->fresh();
        }

        return ServiceJob::create($payload);
    }

    public function normalizeFromTask(Task $task, array $context = []): ServiceJob
    {
        $existing = ServiceJob::where('source_type', 'task')
            ->where('source_id', $task->id)
            ->first();

        $order = $task->order;
        $serviceDomain = data_get($context, 'service_domain', $this->detectServiceDomain($order, $context));

        $payload = [
            'organization_id' => $order?->org_id ?? data_get($context, 'organization_id'),
            'source_type' => 'task',
            'source_id' => $task->id,
            'order_id' => $task->order_id,
            'task_id' => $task->id,
            'service_domain' => $serviceDomain,
            'job_type' => data_get($context, 'job_type', $task->type),
            'status' => $this->mapTaskStatus($task->status),
            'priority' => $task->priority ?? 'normal',
            'service_point' => [
                'address' => $task->address_text,
                'lat' => $task->lat,
                'lng' => $task->lng,
            ],
            'time_window_start' => $task->window_start,
            'time_window_end' => $task->window_end,
            'service_duration_minutes' => $task->expected_duration_min,
            'required_skills' => $task->requirements['skills'] ?? [],
            'required_capacity' => $task->requirements['capacity'] ?? null,
            'required_equipment' => $task->requirements['equipment'] ?? null,
            'zone_id' => $task->zone_id,
            'schedule_slot_id' => $task->slot_id,
            'promised_sla_minutes' => data_get($context, 'promised_sla_minutes', 60),
            'metadata' => [
                'normalized_at' => now()->toIso8601String(),
                'source_status' => $task->status,
            ],
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing->fresh();
        }

        return ServiceJob::create($payload);
    }

    private function detectServiceDomain(?Order $order, array $context): string
    {
        if (! empty($context['service_domain'])) {
            return $context['service_domain'];
        }

        $fromOrder = $order?->service_type;
        if (is_string($fromOrder)) {
            if (str_contains($fromOrder, 'delivery')) {
                return 'delivery';
            }
            if (str_contains($fromOrder, 'handyman') || str_contains($fromOrder, 'repair')) {
                return 'handyman';
            }
        }

        if ($order && HandymanAssignment::where('order_id', $order->id)->exists()) {
            return 'handyman';
        }

        return 'delivery';
    }

    private function deriveWindowEnd($windowStart)
    {
        if (! $windowStart) {
            return null;
        }

        return Carbon::parse($windowStart)->addMinutes(90);
    }

    private function deriveSkills(string $serviceDomain): array
    {
        return match ($serviceDomain) {
            'handyman' => ['on_site_repair', 'customer_support'],
            default => ['pickup', 'delivery'],
        };
    }

    private function mapOrderStatus(?string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            'in_progress' => 'started',
            'confirmed' => 'ready_for_dispatch',
            default => 'pending',
        };
    }

    private function mapTaskStatus(?string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'failed' => 'failed',
            'enroute' => 'en_route',
            'assigned' => 'assigned',
            default => 'pending',
        };
    }
}
