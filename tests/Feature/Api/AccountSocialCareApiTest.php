<?php

namespace Tests\Feature\Api;

use App\Enums\ServiceType;
use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountSocialCareApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_care_index_returns_clients_and_visits_if_user_has_access(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create(['user_id' => $user->id]);
        $careService = CareService::factory()->create(['is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::SOCIAL_CARE_VISIT->value,
        ]);

        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
            'scheduled_start_at' => now()->addDay(),
        ]);

        VisitReport::factory()->create([
            'care_order_details_id' => $careDetails->id,
            'summary' => 'Все хорошо',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/care')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'clients',
                    'active_client',
                    'upcoming_visits',
                    'recent_reports',
                ],
            ]);
    }

    public function test_care_visit_show_returns_details_when_authorized(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create(['user_id' => $user->id]);
        $careService = CareService::factory()->create(['is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::SOCIAL_CARE_VISIT->value,
        ]);

        CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
            'scheduled_start_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/account/care/visits/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id);
    }
}
