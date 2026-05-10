<?php

namespace App\Console\Commands;

use App\Enums\DeliveryTrackingStatus;
use App\Models\Delivery\DeliveryOrder;
use App\Models\DeliveryZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class OpsDeliveryReadinessCommand extends Command
{
    protected $signature = 'ops:delivery-readiness
        {--json= : Optional JSON output path}
        {--min-active-zones=1 : Minimum active delivery zones required for a pass}';

    protected $description = 'Check delivery release guardrails and observability signals';

    public function handle(): int
    {
        $requiredActiveZones = max(1, (int) ($this->option('min-active-zones') ?: 1));
        $configuredTypes = config('delivery.types', []);
        $requiredTypes = ['grocery', 'bulky', 'food'];

        $missingTypes = array_values(array_diff($requiredTypes, array_keys($configuredTypes)));
        $missingTypeKeys = [];

        foreach ($requiredTypes as $type) {
            $config = (array) ($configuredTypes[$type] ?? []);
            $requiredKeys = ['base_time', 'time_per_km'];
            $missingKeys = array_values(array_diff($requiredKeys, array_keys($config)));

            if ($missingKeys !== []) {
                $missingTypeKeys[$type] = $missingKeys;
            }
        }

        $observability = $this->loadObservability();
        $status = (string) ($observability['status'] ?? 'warn');
        $violations = [];

        if ($missingTypes !== []) {
            $status = 'fail';
            $violations[] = [
                'code' => 'missing_delivery_type_config',
                'message' => 'Missing delivery config for: '.implode(', ', $missingTypes),
            ];
        }

        foreach ($missingTypeKeys as $type => $keys) {
            $status = 'fail';
            $violations[] = [
                'code' => 'incomplete_delivery_type_config',
                'message' => sprintf(
                    'Delivery config for %s is missing keys: %s',
                    $type,
                    implode(', ', $keys),
                ),
            ];
        }

        if ((int) ($observability['active_delivery_zones'] ?? 0) < $requiredActiveZones) {
            $status = 'fail';
            $violations[] = [
                'code' => 'active_zones_below_minimum',
                'message' => sprintf(
                    'Active delivery zones (%d) are below the required minimum (%d).',
                    (int) ($observability['active_delivery_zones'] ?? 0),
                    $requiredActiveZones,
                ),
            ];
        }

        if ((int) ($observability['active_orders_missing_courier'] ?? 0) > 0) {
            $status = 'fail';
            $violations[] = [
                'code' => 'active_orders_missing_courier',
                'message' => sprintf(
                    '%d active delivery orders are assigned to fulfillment states without a courier.',
                    (int) ($observability['active_orders_missing_courier'] ?? 0),
                ),
            ];
        }

        if ((int) ($observability['active_orders_missing_route'] ?? 0) > 0) {
            $status = 'fail';
            $violations[] = [
                'code' => 'active_orders_missing_route',
                'message' => sprintf(
                    '%d active delivery orders are missing pickup or delivery coordinates.',
                    (int) ($observability['active_orders_missing_route'] ?? 0),
                ),
            ];
        }

        if ($status !== 'fail' && (int) ($observability['active_orders_missing_eta'] ?? 0) > 0) {
            $status = 'warn';
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'status' => $status,
            'guardrails' => [
                'required_active_zones' => $requiredActiveZones,
                'active_zones' => (int) ($observability['active_delivery_zones'] ?? 0),
                'active_orders' => (int) ($observability['active_delivery_orders'] ?? 0),
                'active_orders_missing_courier' => (int) ($observability['active_orders_missing_courier'] ?? 0),
                'active_orders_missing_eta' => (int) ($observability['active_orders_missing_eta'] ?? 0),
                'active_orders_missing_route' => (int) ($observability['active_orders_missing_route'] ?? 0),
            ],
            'observability' => [
                'source' => (string) ($observability['source'] ?? 'direct'),
                'configured_types' => array_keys($configuredTypes),
                'zone_counts' => $this->zoneCounts(),
                'orders_by_status' => $this->orderCountsByStatus(),
            ],
            'violations' => $violations,
        ];

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-delivery-readiness-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->line('Ops delivery readiness: '.$status);
        $this->line('Active zones: '.$report['guardrails']['active_zones']);
        $this->line('Active orders: '.$report['guardrails']['active_orders']);
        $this->line('Orders missing courier: '.$report['guardrails']['active_orders_missing_courier']);
        $this->line('Orders missing ETA: '.$report['guardrails']['active_orders_missing_eta']);
        $this->line('Report: '.$jsonPath);

        return $status === 'fail' ? self::FAILURE : self::SUCCESS;
    }

    private function loadObservability(): array
    {
        try {
            $row = DB::table('delivery_release_observability')->first();

            if ($row) {
                return [
                    'source' => 'delivery_release_observability_view',
                    'status' => (string) ($row->status ?? 'warn'),
                    'active_delivery_zones' => (int) ($row->active_delivery_zones ?? 0),
                    'active_delivery_orders' => (int) ($row->active_delivery_orders ?? 0),
                    'active_orders_missing_courier' => (int) ($row->active_orders_missing_courier ?? 0),
                    'active_orders_missing_eta' => (int) ($row->active_orders_missing_eta ?? 0),
                    'active_orders_missing_route' => (int) ($row->active_orders_missing_route ?? 0),
                ];
            }
        } catch (Throwable) {
            // Fall back to direct counts when the observability view is absent.
        }

        return [
            'source' => 'direct_query_fallback',
            'status' => $this->computeFallbackStatus(),
            'active_delivery_zones' => DeliveryZone::query()->where('is_active', true)->count(),
            'active_delivery_orders' => DeliveryOrder::query()->active()->count(),
            'active_orders_missing_courier' => DeliveryOrder::query()
                ->whereIn('tracking_status', [
                    DeliveryTrackingStatus::ASSIGNED->value,
                    DeliveryTrackingStatus::PICKED_UP->value,
                    DeliveryTrackingStatus::IN_TRANSIT->value,
                ])
                ->whereNull('courier_id')
                ->count(),
            'active_orders_missing_eta' => DeliveryOrder::query()
                ->active()
                ->whereNull('eta')
                ->count(),
            'active_orders_missing_route' => DeliveryOrder::query()
                ->active()
                ->where(function ($query) {
                    $query->whereNull('pickup_location')
                        ->orWhereNull('delivery_location');
                })
                ->count(),
        ];
    }

    private function computeFallbackStatus(): string
    {
        $activeZones = DeliveryZone::query()->where('is_active', true)->count();
        $missingCourier = DeliveryOrder::query()
            ->whereIn('tracking_status', [
                DeliveryTrackingStatus::ASSIGNED->value,
                DeliveryTrackingStatus::PICKED_UP->value,
                DeliveryTrackingStatus::IN_TRANSIT->value,
            ])
            ->whereNull('courier_id')
            ->count();
        $missingRoute = DeliveryOrder::query()
            ->active()
            ->where(function ($query) {
                $query->whereNull('pickup_location')
                    ->orWhereNull('delivery_location');
            })
            ->count();
        $missingEta = DeliveryOrder::query()
            ->active()
            ->whereNull('eta')
            ->count();

        if ($activeZones === 0 || $missingCourier > 0 || $missingRoute > 0) {
            return 'fail';
        }

        if ($missingEta > 0) {
            return 'warn';
        }

        return 'pass';
    }

    private function zoneCounts(): array
    {
        return DeliveryZone::query()
            ->where('is_active', true)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function orderCountsByStatus(): array
    {
        return DeliveryOrder::query()
            ->select('tracking_status', DB::raw('count(*) as total'))
            ->groupBy('tracking_status')
            ->pluck('total', 'tracking_status')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
