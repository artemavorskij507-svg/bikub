<?php

namespace Database\Seeders;

use App\Models\ErrandOrderDetails;
use App\Models\ErrandTask;
use App\Models\GeoZone;
use App\Models\Moving\ExecutorProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PricingRule;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Errand\ErrandPricingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class ErrandTasksNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Errand Tasks...');

        $geoZone = GeoZone::firstOrCreate(
            ['slug' => 'narvik-personal-errands'],
            [
                'name' => 'Narvik Personal Errands (60km)',
                'description' => 'Зона покрытия индивидуальных поручений GLF в Нарвике и радиусе 60 км',
                'type' => 'service_area',
                'center_latitude' => 68.4378,
                'center_longitude' => 17.4279,
                'radius_meters' => 60000,
                'is_active' => true,
                'metadata' => [
                    'region' => 'Narvik +60km',
                    'service' => 'errands',
                ],
            ]
        );

        $taskBlueprints = [
            [
                'title' => 'Купить обезболивающее в аптеке',
                'category' => 'pharmacy',
                'customer_name' => 'Nora Evensen',
                'address' => 'Dronningens gate 12, Narvik',
                'status' => 'urgent',
                'assigned_worker' => 'Marlene Håkonsen',
                'notes' => 'Аптека Vitus Apotek Narvik',
                'is_urgent' => true,
                'expected_price' => 89,
                'distance_km' => 4.2,
                'duration' => 50,
                'material_advance' => 35000,
            ],
            [
                'title' => 'Забрать документ в NAV Narvik',
                'category' => 'document_service',
                'customer_name' => 'Thomas Bjerke',
                'address' => 'Kongens gate 45, Narvik',
                'status' => 'assigned',
                'assigned_worker' => 'Sivert Mo',
                'notes' => 'Документ выдадут на ресепшн',
                'is_urgent' => false,
                'expected_price' => 99,
                'distance_km' => 3.4,
                'duration' => 40,
                'material_advance' => 0,
            ],
            [
                'title' => 'Передать ключи арендодателю',
                'category' => 'pickup_and_drop',
                'customer_name' => 'Karen Olsen',
                'address' => 'Ankenesveien 90, Ankenes',
                'status' => 'pending',
                'assigned_worker' => 'Liam Karlsen',
                'notes' => 'Передача в 18:00',
                'is_urgent' => false,
                'expected_price' => 59,
                'distance_km' => 12.0,
                'duration' => 65,
                'material_advance' => 0,
            ],
            [
                'title' => 'Купить продукты в Rema 1000',
                'category' => 'purchase_and_deliver',
                'customer_name' => 'Sondre Haugen',
                'address' => 'Skistua 14, Narvik',
                'status' => 'scheduled',
                'assigned_worker' => 'Liam Karlsen',
                'notes' => 'Список: молоко, хлеб, бананы',
                'is_urgent' => false,
                'expected_price' => 79,
                'distance_km' => 9.5,
                'duration' => 70,
                'material_advance' => 40000,
            ],
            [
                'title' => 'Особое поручение — забрать посылку на почте',
                'category' => 'special_errand',
                'customer_name' => 'Ingrid Støle',
                'address' => 'Bjerkvik sentrum',
                'status' => 'pending',
                'assigned_worker' => 'Sivert Mo',
                'notes' => 'Posten Bjerkvik',
                'is_urgent' => false,
                'expected_price' => 149,
                'distance_km' => 45.0,
                'duration' => 120,
                'material_advance' => 0,
            ],
        ];

        $pricingService = app(ErrandPricingService::class);

        foreach ($taskBlueprints as $taskData) {
            $customerEmail = strtolower(str_replace(' ', '.', $taskData['customer_name'])).'@demo.no';
            $customerUser = User::firstOrCreate(
                ['email' => $customerEmail],
                [
                    'name' => $taskData['customer_name'],
                    'password' => Hash::make('password'),
                    'phone' => '+47 '.rand(40000000, 49999999),
                    'is_active' => true,
                ]
            );

            $worker = ExecutorProfile::whereHas('user', fn ($q) => $q->where('name', $taskData['assigned_worker']))->first();

            $serviceType = ServiceType::where('code', $taskData['category'])->first();

            $orderStatus = match ($taskData['status']) {
                'assigned' => 'assigned',
                'scheduled' => 'scheduled',
                default => 'pending',
            };

            $order = Order::create([
                'user_id' => $customerUser->id,
                'order_number' => Order::generateOrderNumber(),
                'service_type' => $serviceType?->code ?? 'errand_personal_task',
                'status' => $orderStatus,
                'geo_zone_id' => $geoZone->id,
                'assigned_to' => $worker?->user_id,
                'total_amount' => $taskData['expected_price'],
                'currency' => 'NOK',
                'payment_status' => $orderStatus === 'assigned' ? 'paid' : 'pending',
                'scheduled_at' => $orderStatus === 'scheduled' ? now()->addDay() : null,
                'notes' => $taskData['notes'],
                'metadata' => [
                    'source' => 'errand-tasks-narvik-seeder',
                    'customer' => $taskData['customer_name'],
                ],
            ]);

            if ($serviceType) {
                $pricingRule = PricingRule::where('service_type_id', $serviceType->id)
                    ->where('name', 'Errand Base Fee')
                    ->first();

                OrderItem::create([
                    'order_id' => $order->id,
                    'service_type_id' => $serviceType->id,
                    'pricing_rule_id' => $pricingRule?->id,
                    'name' => $taskData['title'],
                    'description' => $taskData['notes'],
                    'quantity' => 1,
                    'unit_price' => $taskData['expected_price'],
                    'total_price' => $taskData['expected_price'],
                    'currency' => 'NOK',
                    'metadata' => [
                        'category' => $taskData['category'],
                        'is_urgent' => $taskData['is_urgent'],
                    ],
                ]);
            }

            $details = ErrandOrderDetails::create([
                'order_id' => $order->id,
                'category' => $taskData['category'],
                'description' => $taskData['title'].'. '.$taskData['notes'],
                'from_address' => Arr::get($taskData, 'from_address'),
                'to_address' => $taskData['address'],
                'from_lat' => 68.43800,
                'from_lng' => 17.42700,
                'to_lat' => match (true) {
                    str_contains(strtolower($taskData['address']), 'ankenes') => 68.4200,
                    str_contains(strtolower($taskData['address']), 'bjerkvik') => 68.5460,
                    default => 68.4380,
                },
                'to_lng' => match (true) {
                    str_contains(strtolower($taskData['address']), 'ankenes') => 17.3830,
                    str_contains(strtolower($taskData['address']), 'bjerkvik') => 17.5460,
                    default => 17.4270,
                },
                'desired_start_at' => now()->addHours(2),
                'desired_finish_at' => now()->addHours(4),
                'waypoints' => [],
                'contacts' => [
                    'customer' => [
                        'name' => $taskData['customer_name'],
                        'phone' => $customerUser->phone,
                    ],
                ],
                'is_urgent' => $taskData['is_urgent'],
                'requires_signature' => $taskData['category'] === 'document_service',
                'requires_trusted_helper' => $taskData['category'] === 'special_errand',
                'involves_documents' => in_array($taskData['category'], ['document_service']),
                'complexity_level' => $taskData['category'] === 'special_errand' ? 4 : 2,
                'expected_duration_minutes' => $taskData['duration'],
                'material_advance_amount' => $taskData['material_advance'],
                'executor_profile_id' => $worker?->id,
                'meta' => [
                    'assigned_worker' => $taskData['assigned_worker'],
                    'source' => 'errand-tasks-narvik-seeder',
                ],
            ]);

            $estimate = $pricingService->estimateAndFill($details, $taskData['distance_km']);
            $details->save();

            $pricingSnapshot = [
                'base_fee' => (int) ($details->base_fee ?? 0),
                'distance_fee' => (int) ($details->distance_fee ?? 0),
                'time_fee' => (int) ($details->time_fee ?? 0),
                'complexity_fee' => (int) ($details->complexity_fee ?? 0),
                'trusted_helper_fee' => (int) ($details->trusted_helper_fee ?? 0),
                'urgency_fee' => (int) ($details->urgency_fee ?? 0),
                'material_advance_amount' => (int) ($details->material_advance_amount ?? 0),
                'total_estimated_price' => (int) ($details->total_estimated_price ?? 0),
            ];

            ErrandTask::create([
                'order_id' => $order->id,
                'title' => $taskData['title'],
                'category' => $taskData['category'],
                'description' => $details->description,
                'status' => $taskData['status'],
                'priority' => $taskData['is_urgent'] ? 'high' : 'normal',
                'customer_name' => $taskData['customer_name'],
                'customer_phone' => $customerUser->phone,
                'pickup_address' => Arr::get($taskData, 'from_address', $serviceType?->name ?? 'Narvik sentrum'),
                'from_address' => $details->from_address ?? 'Narvik sentrum',
                'dropoff_address' => $taskData['address'],
                'pickup_location' => ['lat' => 68.4380, 'lng' => 17.4270],
                'dropoff_location' => [
                    'lat' => $details->to_lat,
                    'lng' => $details->to_lng,
                ],
                'from_location' => [
                    'lat' => $details->from_lat ?? 68.4380,
                    'lng' => $details->from_lng ?? 17.4270,
                ],
                'to_location' => [
                    'lat' => $details->to_lat,
                    'lng' => $details->to_lng,
                ],
                'waypoints' => $details->waypoints ?? [],
                'via_points' => $details->waypoints ?? [],
                'contacts' => $details->contacts,
                'notes' => $taskData['notes'],
                'is_urgent' => $taskData['is_urgent'],
                'requires_signature' => $details->requires_signature,
                'requires_trusted_helper' => $details->requires_trusted_helper,
                'requires_document_handling' => $details->involves_documents,
                'expected_duration_minutes' => $taskData['duration'],
                'expected_distance_km' => $taskData['distance_km'],
                'complexity_level' => $details->complexity_level,
                'risk_score' => $taskData['category'] === 'special_errand' ? 2 : 0,
                'material_advance_amount' => $details->material_advance_amount ?? 0,
                'base_fee' => $details->base_fee ?? 0,
                'distance_fee' => $details->distance_fee ?? 0,
                'time_fee' => $details->time_fee ?? 0,
                'complexity_fee' => $details->complexity_fee ?? 0,
                'trusted_helper_fee' => $details->trusted_helper_fee ?? 0,
                'urgency_fee' => $details->urgency_fee ?? 0,
                'estimated_total_amount' => $details->total_estimated_price ?? 0,
                'final_total_amount' => $details->total_estimated_price ?? 0,
                'executor_profile_id' => $worker?->id,
                'geo_zone_id' => $geoZone->id,
                'scheduled_at' => $order->scheduled_at,
                'pricing_snapshot' => $pricingSnapshot,
                'meta' => [
                    'assigned_worker' => $taskData['assigned_worker'],
                ],
            ]);

            $this->command->info("  ✓ Created errand task: {$taskData['title']} ({$taskData['category']})");
        }

        $this->command->info('✅ Narvik Errand Tasks seeded successfully!');
    }
}
