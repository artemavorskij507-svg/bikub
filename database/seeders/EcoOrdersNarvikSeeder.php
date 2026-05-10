<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\DisposalItem;
use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class EcoOrdersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Eco Disposal Orders...');

        // Get or create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@glf.no'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'phone' => '+47 12345678',
                'is_active' => true,
            ]
        );

        // Get Narvik zone
        $narvikZone = GeoZone::where('slug', 'narvik-center')
            ->orWhere('name', 'like', '%Narvik%')
            ->first();

        if (! $narvikZone) {
            $narvikZone = GeoZone::first();
        }

        $orders = [
            [
                'provider' => 'Narvik Renovasjon AS',
                'customer_name' => 'Sofia Lunde',
                'address' => 'Håreks gate 12, 8514 Narvik',
                'status' => 'scheduled',
                'items' => [
                    ['name' => 'Old washing machine', 'category' => 'electronics', 'weight' => 65],
                    ['name' => 'Wooden desk', 'category' => 'furniture', 'weight' => 35],
                ],
            ],
            [
                'provider' => 'Hålogaland Transport & Waste',
                'customer_name' => 'Henrik Solberg',
                'address' => 'Skistua 44, Narvik',
                'status' => 'pending',
                'items' => [
                    ['name' => 'Broken TV', 'category' => 'electronics', 'weight' => 22],
                ],
            ],
            [
                'provider' => 'Ofoten Avfall',
                'customer_name' => 'Emilie Karlsen',
                'address' => 'Ankenesveien 85, Ankenes',
                'status' => 'delivering',
                'items' => [
                    ['name' => 'Fridge', 'category' => 'fridge', 'weight' => 78],
                    ['name' => 'Plastic bags (4)', 'category' => 'mixed', 'weight' => 10],
                ],
            ],
        ];

        foreach ($orders as $orderData) {
            $provider = DisposalPartner::where('name', $orderData['provider'])->first();
            if (! $provider) {
                $this->command->warn("  ⚠ Provider not found: {$orderData['provider']}");

                continue;
            }

            // Get or create disposal items
            $disposalItems = [];
            $totalWeight = 0;
            $totalVolume = 0;

            foreach ($orderData['items'] as $itemData) {
                $disposalItem = DisposalItem::firstOrCreate(
                    ['name' => $itemData['name']],
                    [
                        'category' => $itemData['category'],
                        'weight_kg' => $itemData['weight'],
                        'volume_m3' => $this->estimateVolume($itemData['category'], $itemData['weight']),
                        'requires_disassembly' => in_array($itemData['category'], ['furniture', 'large_appliance']),
                        'difficulty_coefficient' => $itemData['category'] === 'fridge' ? 1.5 : 1.0,
                        'disposal_path' => $this->getDisposalPath($itemData['category']),
                        'eco_score' => $this->getEcoScore($itemData['category']),
                        'base_price_nok' => $this->getBasePrice($itemData['category'], $itemData['weight']),
                        'is_active' => true,
                    ]
                );

                $disposalItems[] = [
                    'disposal_item_id' => $disposalItem->id,
                    'quantity' => 1,
                    'weight_kg' => $itemData['weight'],
                ];

                $totalWeight += $itemData['weight'];
                $totalVolume += $disposalItem->volume_m3 ?? $this->estimateVolume($itemData['category'], $itemData['weight']);
            }

            $orderStatus = match ($orderData['status']) {
                'scheduled' => 'confirmed',
                'delivering' => 'in_progress',
                'pending' => 'pending',
                default => 'pending',
            };

            $ecoStatus = match ($orderData['status']) {
                'scheduled' => 'assigned',
                'delivering' => 'in_transit',
                'pending' => 'pending',
                default => 'pending',
            };

            // Calculate estimated price
            $estimatedPrice = 149; // Base fee
            foreach ($disposalItems as $item) {
                $disposalItem = DisposalItem::find($item['disposal_item_id']);
                if ($disposalItem && $disposalItem->base_price_nok) {
                    $estimatedPrice += $disposalItem->base_price_nok;
                }
            }
            if ($totalWeight > 50) {
                $estimatedPrice += 45; // Heavy item surcharge
            }

            // Get eco team
            $ecoTeam = EcoTeam::where('is_active', true)->first();

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'service_type' => ServiceType::ECO_DISPOSAL->value,
                'status' => $orderStatus,
                'geo_zone_id' => $narvikZone?->id,
                'total_amount' => $estimatedPrice,
                'currency' => 'NOK',
                'payment_status' => $orderStatus === 'confirmed' ? 'paid' : 'pending',
                'scheduled_at' => $orderStatus === 'confirmed' ? now()->addDays(1) : null,
            ]);

            DisposalOrderDetails::create([
                'order_id' => $order->id,
                'items' => $disposalItems,
                'floor' => rand(1, 3),
                'has_elevator' => rand(0, 1) === 1,
                'parking_distance_m' => rand(10, 50),
                'requires_dismantling' => false,
                'express_requested' => false,
                'estimated_volume_m3' => $totalVolume,
                'estimated_weight_kg' => $totalWeight,
                'estimated_price_nok' => $estimatedPrice,
                'eco_partner_hint_id' => $provider->id,
                'eco_team_id' => $ecoTeam?->id,
                'eco_partner_id' => $orderStatus === 'confirmed' ? $provider->id : null,
                'eco_status' => $ecoStatus,
            ]);

            $this->command->info("  ✓ Created eco order #{$order->order_number} for {$orderData['customer_name']} (status: {$orderData['status']})");
        }

        $this->command->info('✅ Narvik Eco Disposal Orders seeded successfully!');
    }

    private function estimateVolume(string $category, float $weight): float
    {
        return match ($category) {
            'fridge' => 1.5,
            'electronics' => 0.3,
            'furniture' => 0.8,
            'mixed' => 0.2,
            default => 0.5,
        };
    }

    private function getDisposalPath(string $category): string
    {
        return match ($category) {
            'electronics', 'fridge' => 'RECYCLABLE',
            'furniture' => 'DONATABLE',
            'mixed' => 'RECYCLABLE',
            default => 'RECYCLABLE',
        };
    }

    private function getEcoScore(string $category): int
    {
        return match ($category) {
            'electronics', 'fridge' => 8,
            'furniture' => 7,
            'mixed' => 6,
            default => 5,
        };
    }

    private function getBasePrice(string $category, float $weight): float
    {
        $base = match ($category) {
            'fridge' => 199,
            'electronics' => 99,
            'furniture' => 149,
            'mixed' => 49,
            default => 99,
        };

        if ($weight > 50) {
            $base += 45; // Heavy item surcharge
        }

        return $base;
    }
}
