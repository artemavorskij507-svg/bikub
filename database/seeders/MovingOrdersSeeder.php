<?php

namespace Database\Seeders;

use App\Models\Moving\MovingOrder;
use App\Models\Moving\Team;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovingOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::firstOrCreate(
            ['email' => 'moving.client@glf.no'],
            [
                'name' => 'Moving Client',
                'password' => 'move-strong-password',
                'phone' => '+4740010203',
                'locale' => 'no',
                'is_active' => true,
            ]
        );

        $orders = [
            [
                'slug' => 'narvik-fagerneset',
                'title' => 'Переезд квартиры 45м² (Narvik → Fagerneset)',
                'from' => [
                    'street' => 'Dronningens gate 35, Narvik',
                    'building_type' => 'apartment',
                    'floor' => 3,
                    'has_elevator' => false,
                ],
                'to' => [
                    'street' => 'Fagernesveien 12, Fagerneset',
                    'building_type' => 'apartment',
                    'floor' => 2,
                    'has_elevator' => false,
                ],
                'distance_km' => 4.1,
                'estimated_duration_text' => '3-5 часов',
                'estimated_duration_minutes' => 240,
                'team' => 'Narvik FlytteService Team A',
                'status' => 'scheduled',
                'package_type' => 'standard',
                'services' => ['packaging', 'assembly'],
                'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            ],
            [
                'slug' => 'ankenes-narvik',
                'title' => 'Переезд частного дома Ankenes → Narvik',
                'from' => [
                    'street' => 'Skogveien 18, Ankenes',
                    'building_type' => 'house',
                    'floor' => 1,
                    'has_elevator' => false,
                ],
                'to' => [
                    'street' => 'Parkgata 4, Narvik',
                    'building_type' => 'apartment',
                    'floor' => 4,
                    'has_elevator' => true,
                ],
                'distance_km' => 7.8,
                'estimated_duration_text' => '5-7 часов',
                'estimated_duration_minutes' => 360,
                'team' => 'Narvik FlytteService Team B',
                'status' => 'pending',
                'package_type' => 'premium',
                'services' => ['packaging', 'assembly', 'takelage'],
                'scheduled_at' => now()->addDays(4)->setTime(10, 30),
            ],
            [
                'slug' => 'bjerkvik-narvik-office',
                'title' => 'Малый офисный переезд Bjerkvik → Narvik sentrum',
                'from' => [
                    'street' => 'Fjordveien 2, Bjerkvik',
                    'building_type' => 'office',
                    'floor' => 1,
                    'has_elevator' => true,
                ],
                'to' => [
                    'street' => 'Kongens gate 67, Narvik sentrum',
                    'building_type' => 'office',
                    'floor' => 5,
                    'has_elevator' => true,
                ],
                'distance_km' => 16.2,
                'estimated_duration_text' => '5-8 часов',
                'estimated_duration_minutes' => 390,
                'team' => 'Bjerkvik Movers',
                'status' => 'scheduled',
                'package_type' => 'standard',
                'services' => ['packaging', 'electronics'],
                'scheduled_at' => now()->addDays(6)->setTime(8, 30),
            ],
        ];

        foreach ($orders as $data) {
            $team = Team::where('name', $data['team'])->first();

            if (! $team) {
                $this->command?->warn("Moving team '{$data['team']}' not found. Run MovingTeamsSeeder first.");

                continue;
            }

            $movingOrder = MovingOrder::where('metadata->slug', $data['slug'])->first();

            if (! $movingOrder) {
                $movingOrder = new MovingOrder;
            }

            // Создать связанный Order
            $order = Order::firstOrCreate(
                [
                    'user_id' => $client->id,
                    'service_type' => 'moving',
                    'status' => $data['status'] === 'scheduled' ? 'confirmed' : 'pending',
                ],
                [
                    'order_number' => 'MOV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                    'total_amount' => 0.00,
                    'currency' => 'NOK',
                    'payment_status' => 'pending',
                    'scheduled_at' => $data['scheduled_at'],
                    'notes' => $data['title'],
                ]
            );

            $movingOrder->fill([
                'user_id' => $client->id,
                'order_id' => $order->id,
                'status' => $data['status'],
                'from_address' => array_merge($data['from'], ['city' => 'Narvik region']),
                'to_address' => array_merge($data['to'], ['city' => 'Narvik region']),
                'package_type' => $data['package_type'],
                'services' => collect($data['services'])->mapWithKeys(fn ($service) => [$service => true])->all(),
                'scheduled_at' => $data['scheduled_at'],
                'executor_team_id' => $team->id,
                'total_volume' => null,
                'total_weight' => null,
                'estimated_price' => null,
                'final_price' => null,
                'estimated_duration_minutes' => $data['estimated_duration_minutes'],
                'customer_notes' => $data['title'],
                'metadata' => [
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                    'distance_km' => $data['distance_km'],
                    'estimated_duration_text' => $data['estimated_duration_text'],
                ],
            ]);

            $movingOrder->save();
        }
    }
}
