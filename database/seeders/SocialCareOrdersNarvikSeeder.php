<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\CareOrderDetails;
use App\Models\CarePlan;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class SocialCareOrdersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Social Care Orders...');

        $narvikZone = GeoZone::where('slug', 'narvik-center')
            ->orWhere('name', 'like', '%Narvik%')
            ->first();

        $orders = [
            [
                'customer_name' => 'Liv Andersen',
                'address' => 'Dronningens gate 26, Narvik',
                'service_type' => 'shopping_assist',
                'status' => 'scheduled',
                'plan' => 'Hjemmehjelp Basic',
                'assigned_helper' => 'Eva Nystad',
                'notes' => 'Еженедельные покупки',
            ],
            [
                'customer_name' => 'Arne Pedersen',
                'address' => 'Ankenesveien 40, Ankenes',
                'service_type' => 'medical_escort',
                'status' => 'assigned',
                'plan' => 'Eldrehjelp Plus',
                'assigned_helper' => 'Kari Stenersen',
                'notes' => 'Поездка в Narvik Legevakt',
            ],
            [
                'customer_name' => 'Nina Johansen',
                'address' => 'Fjellveien 55, Bjerkvik',
                'service_type' => 'social_visit',
                'status' => 'pending',
                'plan' => 'Funksjonsassistanse',
                'assigned_helper' => 'Ida Moen',
                'notes' => 'Социальная беседа раз в неделю',
            ],
        ];

        foreach ($orders as $orderData) {
            // Find client user
            $clientUser = User::where('name', $orderData['customer_name'])->first();
            if (! $clientUser) {
                $this->command->warn("  ⚠ Client not found: {$orderData['customer_name']}");

                continue;
            }

            $clientProfile = ClientProfile::where('user_id', $clientUser->id)->first();
            if (! $clientProfile) {
                $this->command->warn("  ⚠ ClientProfile not found for: {$orderData['customer_name']}");

                continue;
            }

            // Find care service
            $careService = CareService::where('code', $orderData['service_type'])->first();
            if (! $careService) {
                $this->command->warn("  ⚠ CareService not found: {$orderData['service_type']}");

                continue;
            }

            // Find care plan
            $carePlan = CarePlan::where('client_profile_id', $clientProfile->id)
                ->where('care_service_id', $careService->id)
                ->first();

            // Find helper
            $helper = SocialHelperProfile::whereHas('user', function ($q) use ($orderData) {
                $q->where('name', $orderData['assigned_helper']);
            })->first();

            // Map status
            $orderStatus = match ($orderData['status']) {
                'scheduled' => 'scheduled',
                'assigned' => 'assigned',
                'pending' => 'pending',
                default => 'pending',
            };

            $careStatus = match ($orderData['status']) {
                'scheduled' => \App\Enums\CareOrderStatus::SCHEDULED->value,
                'assigned' => \App\Enums\CareOrderStatus::ACCEPTED_BY_HELPER->value,
                'pending' => \App\Enums\CareOrderStatus::PENDING->value,
                default => \App\Enums\CareOrderStatus::PENDING->value,
            };

            // Create Order
            $order = Order::create([
                'user_id' => $clientUser->id,
                'order_number' => Order::generateOrderNumber(),
                'service_type' => ServiceType::SOCIAL_CARE_VISIT->value,
                'status' => $orderStatus,
                'geo_zone_id' => $narvikZone?->id,
                'assigned_to' => $helper?->user_id,
                'total_amount' => $careService->base_price_nok,
                'currency' => 'NOK',
                'payment_status' => $orderStatus === 'assigned' ? 'paid' : 'pending',
                'scheduled_at' => $orderStatus === 'scheduled' ? now()->addDays(rand(1, 7)) : null,
                'notes' => $orderData['notes'],
            ]);

            // Create CareOrderDetails
            $scheduledStart = $orderStatus === 'scheduled'
                ? now()->addDays(rand(1, 7))->setHour(10)
                : ($orderStatus === 'assigned' ? now()->addDays(rand(1, 3))->setHour(10) : now()->addDays(rand(1, 7))->setHour(10));
            $scheduledEnd = $scheduledStart->copy()->addHours(2);

            CareOrderDetails::create([
                'order_id' => $order->id,
                'client_profile_id' => $clientProfile->id,
                'care_service_id' => $careService->id,
                'care_plan_id' => $carePlan?->id,
                'care_status' => $careStatus,
                'scheduled_start_at' => $scheduledStart,
                'scheduled_end_at' => $scheduledEnd,
                'assigned_helper_id' => $helper?->id,
                'requested_helper_level' => 'SOCIAL_HELPER',
                'price_nok' => $careService->base_price_nok,
                'notes_for_helper' => $orderData['notes'],
            ]);

            $this->command->info("  ✓ Created social care order #{$order->order_number} for {$orderData['customer_name']} (status: {$orderData['status']})");
        }

        $this->command->info('✅ Narvik Social Care Orders seeded successfully!');
    }
}
