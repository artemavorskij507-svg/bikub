<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\Delivery\DeliveryOrder;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ListDeliveryOrders extends ListRecords
{
    protected static string $resource = DeliveryOrderResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedDeliveryOrdersIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedDeliveryOrdersIfEmpty(): void
    {
        if (! Schema::hasTable('delivery_orders') || ! Schema::hasTable('orders')) {
            return;
        }

        if (DeliveryOrder::query()->exists()) {
            return;
        }

        try {
            $orderId = DB::table('orders')->value('id');

            if (! $orderId) {
                $orderId = $this->createCompatibleDemoOrder();
            }

            if (! $orderId) {
                return;
            }

            $this->insertCompatibleDemoDeliveryOrder($orderId);
        } catch (Throwable) {
            // Keep admin page usable even if demo seed fails in unusual local schemas.
        }
    }

    protected function createCompatibleDemoOrder(): int|string|null
    {
        $columns = collect(DB::select("PRAGMA table_info('orders')"));
        if ($columns->isEmpty()) {
            return null;
        }

        $columnNames = $columns->pluck('name')->map(fn ($name) => (string) $name)->all();
        $fkMap = collect(DB::select("PRAGMA foreign_key_list('orders')"))
            ->mapWithKeys(fn ($fk) => [(string) $fk->from => ['table' => (string) $fk->table, 'to' => (string) $fk->to]])
            ->all();

        $now = now()->toDateTimeString();
        $row = [];

        foreach ($columns as $column) {
            $name = (string) $column->name;
            $type = strtolower((string) ($column->type ?? ''));
            $isNotNull = ((int) ($column->notnull ?? 0)) === 1;
            $default = $column->dflt_value ?? null;
            $isPrimary = ((int) ($column->pk ?? 0)) === 1;

            if ($isPrimary) {
                continue;
            }

            if (! $isNotNull || $default !== null) {
                continue;
            }

            if (isset($fkMap[$name])) {
                $refTable = $fkMap[$name]['table'];
                $refColumn = $fkMap[$name]['to'];

                if (! Schema::hasTable($refTable)) {
                    return null;
                }

                $refValue = DB::table($refTable)->value($refColumn);
                if ($refValue === null) {
                    return null;
                }

                $row[$name] = $refValue;
                continue;
            }

            if (in_array($name, ['created_at', 'updated_at'], true)) {
                $row[$name] = $now;
                continue;
            }

            if ($name === 'deleted_at') {
                continue;
            }

            if (str_contains($name, 'order_number')) {
                $row[$name] = 'ORD-DEMO-'.now()->format('YmdHis');
                continue;
            }

            if (str_contains($name, 'status')) {
                $row[$name] = 'pending';
                continue;
            }

            if (str_contains($name, 'service_type') || str_contains($name, 'type')) {
                $row[$name] = 'delivery';
                continue;
            }

            if (str_contains($name, 'currency')) {
                $row[$name] = 'NOK';
                continue;
            }

            if (str_contains($name, 'uuid') || str_contains($name, 'token')) {
                $row[$name] = (string) Str::uuid();
                continue;
            }

            if (str_contains($name, 'json') || in_array($name, ['location', 'metadata', 'payload'], true)) {
                $row[$name] = '{}';
                continue;
            }

            if (str_contains($type, 'int') || str_contains($type, 'real') || str_contains($type, 'dec') || str_contains($type, 'num')) {
                $row[$name] = 0;
                continue;
            }

            if (str_contains($type, 'date') || str_contains($type, 'time')) {
                $row[$name] = $now;
                continue;
            }

            $row[$name] = 'demo';
        }

        if (in_array('created_at', $columnNames, true) && ! isset($row['created_at'])) {
            $row['created_at'] = $now;
        }

        if (in_array('updated_at', $columnNames, true) && ! isset($row['updated_at'])) {
            $row['updated_at'] = $now;
        }

        if (empty($row)) {
            return null;
        }

        return DB::table('orders')->insertGetId($row);
    }

    protected function insertCompatibleDemoDeliveryOrder(int|string $orderId): void
    {
        $columns = collect(DB::select("PRAGMA table_info('delivery_orders')"))
            ->pluck('name')
            ->map(fn ($column) => (string) $column)
            ->all();

        $now = now();
        $row = [
            'order_id' => $orderId,
            'type' => 'grocery',
            'pickup_location' => json_encode([
                'lat' => 59.9139,
                'lng' => 10.7522,
                'address' => 'Oslo Central Hub',
            ], JSON_UNESCAPED_UNICODE),
            'delivery_location' => json_encode([
                'lat' => 59.92,
                'lng' => 10.76,
                'address' => 'Karl Johans gate 1, Oslo',
            ], JSON_UNESCAPED_UNICODE),
            'pickup_address' => 'Oslo Central Hub',
            'delivery_address' => 'Karl Johans gate 1, Oslo',
            'estimated_distance_km' => 3.4,
            'estimated_duration_minutes' => 18,
            'eta' => $now->copy()->addMinutes(25)->toDateTimeString(),
            'tracking_status' => 'pending',
            'substitution_policy' => 'contact',
            'is_urgent' => 0,
            'metadata' => json_encode([
                'source' => 'local_demo_seed',
                'note' => 'Demo delivery order',
            ], JSON_UNESCAPED_UNICODE),
            'tracking_token' => (string) Str::uuid(),
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ];

        $prepared = [];
        foreach ($row as $key => $value) {
            if (in_array($key, $columns, true)) {
                $prepared[$key] = $value;
            }
        }

        if (! isset($prepared['order_id'], $prepared['type'])) {
            return;
        }

        DB::table('delivery_orders')->insert($prepared);
    }
}