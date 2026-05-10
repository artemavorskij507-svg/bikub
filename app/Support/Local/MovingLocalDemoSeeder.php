<?php

namespace App\Support\Local;

use App\Models\Moving\ExecutorProfile;
use App\Models\Moving\MovingItem;
use App\Models\Moving\MovingOrder;
use App\Models\Moving\Team;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MovingLocalDemoSeeder
{
    public static function run(): void
    {
        if (! self::isLocalRuntime()) {
            return;
        }

        self::ensureSchema();

        if (! Schema::hasTable('users')) {
            return;
        }

        $userIds = DB::table('users')->limit(5)->pluck('id')->filter()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        self::seedExecutorProfiles($userIds->all());
        self::seedTeams($userIds->all());
        self::seedMovingOrders($userIds->all());
        self::seedMovingItems();
        self::seedMovingOrderPhotos();
    }

    protected static function ensureSchema(): void
    {
        $paths = [
            'database/migrations/2025_11_13_020853_create_executor_profiles_table.php',
            'database/migrations/2025_11_13_020854_create_teams_table.php',
            'database/migrations/2025_11_13_020855_create_team_user_table.php',
            'database/migrations/2025_11_13_020856_create_moving_orders_table.php',
            'database/migrations/2025_11_13_020857_create_moving_items_table.php',
            'database/migrations/2025_11_13_021021_create_moving_order_photos_table.php',
        ];

        foreach ($paths as $path) {
            if (! is_file(base_path($path))) {
                continue;
            }

            try {
                Artisan::call('migrate', [
                    '--path' => $path,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Moving local demo: migrate failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected static function seedExecutorProfiles(array $userIds): void
    {
        if (! Schema::hasTable('executor_profiles') || ExecutorProfile::query()->exists()) {
            return;
        }

        foreach (array_slice($userIds, 0, 3) as $index => $userId) {
            $vehicle = ['van', 'truck', 'with_lift'][$index % 3];

            ExecutorProfile::query()->create([
                'user_id' => $userId,
                'vehicle_type' => $vehicle,
                'skills' => ['moving', 'packing'],
                'max_volume' => 18 + ($index * 4),
                'max_weight' => 1800 + ($index * 250),
                'rating' => 4.6 - ($index * 0.1),
                'completed_orders_count' => 12 + ($index * 3),
                'is_active' => true,
                'metadata' => ['seed' => 'local_demo'],
            ]);
        }
    }

    protected static function seedTeams(array $userIds): void
    {
        if (! Schema::hasTable('teams') || Team::query()->exists()) {
            return;
        }

        $leaderId = $userIds[0];
        $backupLeaderId = $userIds[1] ?? $leaderId;

        $teams = [
            [
                'name' => 'Moving Team North',
                'description' => 'Apartment and office moves in central zones.',
                'leader_id' => $leaderId,
                'status' => 'active',
                'max_orders' => 6,
                'rating' => 4.8,
                'completed_orders_count' => 44,
                'specializations' => ['moving', 'packing', 'electronics'],
                'metadata' => ['seed' => 'local_demo', 'zone' => 'north'],
            ],
            [
                'name' => 'Moving Team South',
                'description' => 'Bulky moves and long-distance routes.',
                'leader_id' => $backupLeaderId,
                'status' => 'active',
                'max_orders' => 5,
                'rating' => 4.7,
                'completed_orders_count' => 31,
                'specializations' => ['moving', 'takelage', 'disposal'],
                'metadata' => ['seed' => 'local_demo', 'zone' => 'south'],
            ],
        ];

        foreach ($teams as $teamData) {
            $team = Team::query()->create($teamData);

            if (Schema::hasTable('team_user')) {
                $attachIds = array_slice($userIds, 0, 3);
                foreach ($attachIds as $uid) {
                    DB::table('team_user')->updateOrInsert(
                        ['team_id' => $team->id, 'user_id' => $uid],
                        [
                            'role' => $uid === $team->leader_id ? 'leader' : 'member',
                            'joined_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    protected static function seedMovingOrders(array $userIds): void
    {
        if (! Schema::hasTable('moving_orders')) {
            return;
        }

        self::backfillMissingOrderIds();

        if (MovingOrder::query()->count() >= 3) {
            return;
        }

        $teamIds = Schema::hasTable('teams')
            ? Team::query()->pluck('id')->all()
            : [];

        $orders = [
            [
                'user_id' => $userIds[0],
                'status' => 'pending',
                'from_address' => [
                    'street' => 'Storgata 14',
                    'city' => 'Oslo',
                    'postal_code' => '0155',
                    'lat' => 59.9139,
                    'lng' => 10.7522,
                    'building_type' => 'apartment',
                    'floor' => 2,
                    'has_elevator' => false,
                ],
                'to_address' => [
                    'street' => 'Dronning Eufemias gate 22',
                    'city' => 'Oslo',
                    'postal_code' => '0191',
                    'lat' => 59.9074,
                    'lng' => 10.7579,
                    'building_type' => 'apartment',
                    'floor' => 4,
                    'has_elevator' => true,
                ],
                'services' => ['packing' => true, 'assembly' => true, 'disposal' => false],
                'package_type' => 'standard',
                'scheduled_at' => now()->addDay(),
                'executor_team_id' => $teamIds[0] ?? null,
                'total_volume' => 11.4,
                'total_weight' => 1270,
                'estimated_price' => 5890,
                'estimated_duration_minutes' => 300,
                'customer_notes' => 'Call 30 minutes before arrival.',
                'metadata' => ['seed' => 'local_demo'],
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[0],
                'status' => 'confirmed',
                'from_address' => [
                    'street' => 'Nedre Slottsgate 3',
                    'city' => 'Oslo',
                    'postal_code' => '0157',
                    'lat' => 59.9127,
                    'lng' => 10.7461,
                    'building_type' => 'office',
                    'floor' => 5,
                    'has_elevator' => true,
                ],
                'to_address' => [
                    'street' => 'Schweigaards gate 53',
                    'city' => 'Oslo',
                    'postal_code' => '0191',
                    'lat' => 59.9122,
                    'lng' => 10.7691,
                    'building_type' => 'office',
                    'floor' => 3,
                    'has_elevator' => true,
                ],
                'services' => ['packing' => true, 'assembly' => false, 'disposal' => true],
                'package_type' => 'premium',
                'scheduled_at' => now()->addDays(2),
                'executor_team_id' => $teamIds[1] ?? ($teamIds[0] ?? null),
                'total_volume' => 19.8,
                'total_weight' => 2120,
                'estimated_price' => 9200,
                'estimated_duration_minutes' => 420,
                'customer_notes' => 'Fragile office equipment and monitors.',
                'metadata' => ['seed' => 'local_demo'],
            ],
            [
                'user_id' => $userIds[2] ?? $userIds[0],
                'status' => 'in_progress',
                'from_address' => [
                    'street' => 'Trondheimsveien 45',
                    'city' => 'Oslo',
                    'postal_code' => '0560',
                    'lat' => 59.9285,
                    'lng' => 10.7724,
                    'building_type' => 'house',
                    'floor' => 1,
                    'has_elevator' => false,
                ],
                'to_address' => [
                    'street' => 'Helgesens gate 30',
                    'city' => 'Oslo',
                    'postal_code' => '0553',
                    'lat' => 59.9232,
                    'lng' => 10.7580,
                    'building_type' => 'house',
                    'floor' => 1,
                    'has_elevator' => false,
                ],
                'services' => ['packing' => false, 'assembly' => true, 'disposal' => false],
                'package_type' => 'economy',
                'scheduled_at' => now()->subHours(1),
                'executor_team_id' => $teamIds[0] ?? null,
                'total_volume' => 7.2,
                'total_weight' => 840,
                'estimated_price' => 3990,
                'estimated_duration_minutes' => 180,
                'customer_notes' => 'No truck parking near the entrance.',
                'metadata' => ['seed' => 'local_demo'],
            ],
        ];

        foreach ($orders as $payload) {
            if (MovingOrder::query()->count() >= 3) {
                break;
            }

            $payload['order_id'] = self::ensureOrderIdForMoving(
                (int) $payload['user_id'],
                (string) $payload['status'],
                $payload['scheduled_at'] ?? null
            );

            MovingOrder::query()->create($payload);
        }
    }

    protected static function backfillMissingOrderIds(): void
    {
        if (! Schema::hasTable('moving_orders') || ! Schema::hasTable('orders')) {
            return;
        }

        $records = MovingOrder::query()
            ->whereNull('order_id')
            ->limit(20)
            ->get(['id', 'user_id', 'status', 'scheduled_at']);

        foreach ($records as $record) {
            $orderId = self::ensureOrderIdForMoving(
                (int) $record->user_id,
                (string) $record->status,
                $record->scheduled_at
            );

            if ($orderId) {
                $record->forceFill(['order_id' => $orderId])->saveQuietly();
            }
        }
    }

    protected static function seedMovingItems(): void
    {
        if (! Schema::hasTable('moving_items') || MovingItem::query()->exists()) {
            return;
        }

        $orders = MovingOrder::query()->orderBy('id')->get();
        if ($orders->isEmpty()) {
            return;
        }

        $itemsByOrder = [
            [
                ['name' => 'Corner Sofa', 'category' => 'furniture', 'volume' => 3.2, 'weight' => 85, 'quantity' => 1, 'requires_assembly' => true, 'is_fragile' => false],
                ['name' => 'Dining Table', 'category' => 'furniture', 'volume' => 1.8, 'weight' => 46, 'quantity' => 1, 'requires_assembly' => true, 'is_fragile' => false],
                ['name' => 'TV 55"', 'category' => 'electronics', 'volume' => 0.6, 'weight' => 18, 'quantity' => 1, 'requires_assembly' => false, 'is_fragile' => true],
                ['name' => 'Boxes Large', 'category' => 'boxes', 'volume' => 0.25, 'weight' => 12, 'quantity' => 12, 'requires_assembly' => false, 'is_fragile' => false],
            ],
            [
                ['name' => 'Office Desk', 'category' => 'furniture', 'volume' => 1.4, 'weight' => 39, 'quantity' => 4, 'requires_assembly' => true, 'is_fragile' => false],
                ['name' => 'Monitor 27"', 'category' => 'electronics', 'volume' => 0.18, 'weight' => 6, 'quantity' => 10, 'requires_assembly' => false, 'is_fragile' => true],
                ['name' => 'Office Chair', 'category' => 'furniture', 'volume' => 0.7, 'weight' => 11, 'quantity' => 8, 'requires_assembly' => false, 'is_fragile' => false],
            ],
            [
                ['name' => 'Washing Machine', 'category' => 'electronics', 'volume' => 0.9, 'weight' => 74, 'quantity' => 1, 'requires_assembly' => false, 'is_fragile' => true],
                ['name' => 'Bed Frame', 'category' => 'furniture', 'volume' => 2.1, 'weight' => 52, 'quantity' => 1, 'requires_assembly' => true, 'is_fragile' => false],
                ['name' => 'Moving Boxes', 'category' => 'boxes', 'volume' => 0.22, 'weight' => 9, 'quantity' => 8, 'requires_assembly' => false, 'is_fragile' => false],
            ],
        ];

        foreach ($orders as $index => $order) {
            $itemSet = $itemsByOrder[$index] ?? $itemsByOrder[0];

            foreach ($itemSet as $sort => $item) {
                MovingItem::query()->create([
                    'moving_order_id' => $order->id,
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'volume' => $item['volume'],
                    'weight' => $item['weight'],
                    'quantity' => $item['quantity'],
                    'requires_assembly' => $item['requires_assembly'],
                    'is_fragile' => $item['is_fragile'],
                    'notes' => 'Seeded demo item',
                    'sort_order' => $sort,
                ]);
            }
        }
    }

    protected static function seedMovingOrderPhotos(): void
    {
        if (! Schema::hasTable('moving_order_photos')) {
            return;
        }

        if (DB::table('moving_order_photos')->exists()) {
            return;
        }

        $orders = MovingOrder::query()->orderBy('id')->limit(3)->get();
        if ($orders->isEmpty()) {
            return;
        }

        $collections = ['pre_move_photos', 'post_move_photos', 'damage_photos'];

        foreach ($orders as $index => $order) {
            DB::table('moving_order_photos')->insert([
                'moving_order_id' => $order->id,
                'file_path' => 'moving-orders/photos/demo-placeholder-'.$order->id.'.jpg',
                'file_name' => 'demo-placeholder-'.$order->id.'.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 0,
                'collection_name' => $collections[$index % count($collections)],
                'description' => 'Local demo photo for moving order '.$order->id,
                'metadata' => json_encode(['seed' => 'local_demo']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected static function isLocalRuntime(): bool
    {
        $host = request()->getHost();

        return app()->environment('local') || in_array($host, ['127.0.0.1', 'localhost'], true);
    }

    protected static function ensureOrderIdForMoving(int $userId, string $status, mixed $scheduledAt): ?int
    {
        if (! Schema::hasTable('orders')) {
            return null;
        }

        $now = now();
        $orderNumber = 'MOV-'.strtoupper(Str::random(10));

        $row = [];

        if (Schema::hasColumn('orders', 'order_number')) {
            $row['order_number'] = $orderNumber;
        }
        if (Schema::hasColumn('orders', 'user_id')) {
            $row['user_id'] = $userId;
        }
        if (Schema::hasColumn('orders', 'status')) {
            $row['status'] = in_array($status, ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], true)
                ? $status
                : 'pending';
        }
        if (Schema::hasColumn('orders', 'priority')) {
            $row['priority'] = 'normal';
        }
        if (Schema::hasColumn('orders', 'scheduled_at') && $scheduledAt) {
            $row['scheduled_at'] = $scheduledAt;
        }
        if (Schema::hasColumn('orders', 'total_amount')) {
            $row['total_amount'] = 0;
        }
        if (Schema::hasColumn('orders', 'currency')) {
            $row['currency'] = 'NOK';
        }
        if (Schema::hasColumn('orders', 'payment_status')) {
            $row['payment_status'] = 'pending';
        }
        if (Schema::hasColumn('orders', 'service_type')) {
            $row['service_type'] = 'moving';
        }
        if (Schema::hasColumn('orders', 'metadata')) {
            $row['metadata'] = json_encode([
                'seed' => 'local_demo',
                'service_type' => 'moving',
            ]);
        }
        if (Schema::hasColumn('orders', 'created_at')) {
            $row['created_at'] = $now;
        }
        if (Schema::hasColumn('orders', 'updated_at')) {
            $row['updated_at'] = $now;
        }

        try {
            return (int) DB::table('orders')->insertGetId($row);
        } catch (\Throwable $e) {
            Log::warning('Moving local demo: failed to create base order', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return Schema::hasColumn('orders', 'user_id')
                ? (int) (DB::table('orders')->where('user_id', $userId)->value('id') ?: 0)
                : (int) (DB::table('orders')->value('id') ?: 0);
        }
    }
}
