<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideAssistanceDetail;
use App\Models\RoadsideEmergency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoadsideOrdersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Roadside Orders...');

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
                'customer_name' => 'Markus Aas',
                'vehicle' => 'Volkswagen Passat 2014',
                'location' => 'E6 Narvik Tunnel Exit',
                'status' => 'assigned',
                'job_type' => 'tow',
                'partner' => 'Narvik Bilberging AS',
                'notes' => 'Двигатель не заводится',
            ],
            [
                'customer_name' => 'Ida Kristiansen',
                'vehicle' => 'Toyota RAV4 2018',
                'location' => 'Ankenesveien 105, Ankenes',
                'status' => 'pending',
                'job_type' => 'tire_change',
                'partner' => 'Frydenlund Bilservice',
                'notes' => 'Пробитое колесо',
            ],
            [
                'customer_name' => 'Oskar Nygaard',
                'vehicle' => 'Tesla Model 3',
                'location' => 'Bjerkvik Sentrum',
                'status' => 'delivering',
                'job_type' => 'unlock',
                'partner' => 'Ofoten Road Rescue',
                'notes' => 'Заблокированы двери, ключи внутри',
            ],
        ];

        foreach ($orders as $orderData) {
            $partner = Partner::where('name', $orderData['partner'])->first();
            if (! $partner) {
                $this->command->warn("  ⚠ Partner not found: {$orderData['partner']}");

                continue;
            }

            // Get helper for this partner (via metadata)
            $helper = RoadHelperProfile::whereJsonContains('metadata->roadside_partner_id', $partner->id)
                ->first();

            $orderStatus = match ($orderData['status']) {
                'assigned' => 'assigned',
                'delivering' => 'in_progress',
                'pending' => 'pending',
                default => 'pending',
            };

            $emergencyStatus = match ($orderData['status']) {
                'assigned' => 'assigned',
                'delivering' => 'on_route',
                'pending' => 'new',
                default => 'new',
            };

            // Map job_type to incident_type
            $incidentType = match ($orderData['job_type']) {
                'tow' => 'tow_needed',
                'tire_change' => 'flat_tire',
                'unlock' => 'locked_keys',
                'jump_start' => 'jump_start',
                'fuel_delivery' => 'fuel',
                'diagnostics' => 'engine_no_start',
                default => 'engine_no_start',
            };

            // Get base price from preset
            $preset = \App\Models\RoadsidePreset::where('code', $orderData['job_type'])->first();
            $basePrice = $preset?->base_price ?? 299.00;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'service_type' => ServiceType::ROAD_ASSIST->value,
                'status' => $orderStatus,
                'geo_zone_id' => $narvikZone?->id,
                'roadside_partner_id' => $orderStatus === 'assigned' ? $partner->id : null,
                'total_amount' => $basePrice,
                'currency' => 'NOK',
                'payment_status' => $orderStatus === 'assigned' ? 'paid' : 'pending',
                'scheduled_at' => $orderStatus === 'assigned' ? now()->addHours(1) : null,
                'notes' => $orderData['notes'],
            ]);

            // Parse vehicle info
            $vehicleParts = explode(' ', $orderData['vehicle']);
            $vehicleMake = $vehicleParts[0] ?? 'Unknown';
            $vehicleModel = implode(' ', array_slice($vehicleParts, 1)) ?? 'Unknown';

            RoadsideAssistanceDetail::create([
                'order_id' => $order->id,
                'subtype' => $orderData['job_type'],
                'incident_address' => $orderData['location'],
                'incident_lat' => 68.43800,
                'incident_lng' => 17.42700,
                'vehicle_make' => $vehicleMake,
                'vehicle_model' => $vehicleModel,
                'vehicle_plate' => 'XX'.rand(10000, 99999),
                'vehicle_color' => ['Red', 'Blue', 'Black', 'White', 'Silver'][rand(0, 4)],
                'partner_id' => $orderStatus === 'assigned' ? $partner->id : null,
                'extra' => [
                    'notes' => $orderData['notes'],
                    'customer_name' => $orderData['customer_name'],
                ],
            ]);

            // Create RoadsideEmergency without triggering observer (to avoid notification errors)
            $roadsideEmergency = new RoadsideEmergency([
                'customer_id' => $user->id,
                'road_helper_id' => $helper?->id,
                'resolved_by_partner_id' => $orderStatus === 'assigned' ? $partner->id : null,
                'order_id' => $order->id,
                'incident_type' => $incidentType,
                'incident_description' => $orderData['notes'],
                'lat' => 68.43800,
                'lng' => 17.42700,
                'status' => $emergencyStatus,
                'tracking_token' => (string) Str::uuid(),
                'metadata' => [
                    'customer_name' => $orderData['customer_name'],
                    'vehicle' => $orderData['vehicle'],
                    'location' => $orderData['location'],
                ],
            ]);
            $roadsideEmergency->saveQuietly(); // Save without triggering events/observers

            $this->command->info("  ✓ Created roadside order #{$order->order_number} for {$orderData['customer_name']} (status: {$orderData['status']})");
        }

        $this->command->info('✅ Narvik Roadside Orders seeded successfully!');
    }
}
