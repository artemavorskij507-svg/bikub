<?php

namespace Tests\Feature\Api\V1\Helper;

use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\CommunityPointsBalance;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HelperApiTest extends TestCase
{
    use RefreshDatabase;

    private function createHelper(): array
    {
        $user = User::factory()->create();
        $helper = SocialHelperProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        return ['user' => $user, 'helper' => $helper];
    }

    /** @test */
    public function helper_can_fetch_profile_me(): void
    {
        ['user' => $user, 'helper' => $helper] = $this->createHelper();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/helper/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'name',
                    'level',
                    'is_active',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $helper->id,
                    'level' => $helper->level,
                    'is_active' => true,
                ],
            ]);
    }

    /** @test */
    public function helper_cannot_access_if_profile_inactive(): void
    {
        $user = User::factory()->create();
        SocialHelperProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/helper/me');

        $response->assertForbidden();
    }

    /** @test */
    public function helper_can_see_upcoming_visits_only_for_himself(): void
    {
        ['user' => $userA, 'helper' => $helperA] = $this->createHelper();
        ['user' => $userB, 'helper' => $helperB] = $this->createHelper();

        $client = ClientProfile::factory()->create();
        $service = CareService::factory()->create();

        $orderA = $this->createCareOrder($client, $service, $helperA, 'SCHEDULED');
        $orderB = $this->createCareOrder($client, $service, $helperB, 'SCHEDULED');

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/helper/visits/upcoming');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_id', $orderA->id);
    }

    /** @test */
    public function helper_can_accept_and_finish_visit(): void
    {
        ['user' => $user, 'helper' => $helper] = $this->createHelper();

        $client = ClientProfile::factory()->create();
        $service = CareService::factory()->create();

        $order = $this->createCareOrder($client, $service, $helper, 'SCHEDULED');

        Sanctum::actingAs($user);

        // Accept
        $response = $this->postJson("/api/v1/helper/visits/{$order->id}/accept");
        $response->assertOk()
            ->assertJsonPath('data.care_status', 'ACCEPTED_BY_HELPER');

        $order->refresh();
        $this->assertEquals('ACCEPTED_BY_HELPER', $order->careDetails->care_status);

        // Start
        $response = $this->postJson("/api/v1/helper/visits/{$order->id}/start");
        $response->assertOk()
            ->assertJsonPath('data.care_status', 'IN_PROGRESS');

        // Finish
        $response = $this->postJson("/api/v1/helper/visits/{$order->id}/finish", [
            'summary' => 'Visit completed successfully',
            'status' => 'COMPLETED',
            'client_mood' => 'HAPPY',
            'followup_recommended' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.care_status', 'COMPLETED')
            ->assertJsonStructure([
                'data' => [
                    'visit_report',
                ],
            ]);

        $order->refresh();
        $this->assertEquals('COMPLETED', $order->careDetails->care_status);
        $this->assertTrue($order->careDetails->visitReports()->exists());
    }

    /** @test */
    public function helper_can_view_stats(): void
    {
        ['user' => $user, 'helper' => $helper] = $this->createHelper();

        CommunityPointsBalance::create([
            'helper_profile_id' => $helper->id,
            'balance_points' => 150,
            'lifetime_points' => 500,
        ]);

        $client = ClientProfile::factory()->create();
        $service = CareService::factory()->create();

        // Create completed visits
        $this->createCareOrder($client, $service, $helper, 'COMPLETED', now()->subHours(2));
        $this->createCareOrder($client, $service, $helper, 'COMPLETED', now()->subDays(3));

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/helper/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'visits_today',
                    'visits_this_week',
                    'visits_total',
                    'rating_avg',
                    'rating_count',
                    'points_balance',
                    'points_lifetime',
                ],
            ])
            ->assertJsonPath('data.visits_today', 1)
            ->assertJsonPath('data.visits_this_week', 2)
            ->assertJsonPath('data.visits_total', 2)
            ->assertJsonPath('data.points_balance', 150)
            ->assertJsonPath('data.points_lifetime', 500);
    }

    private function createCareOrder(
        ClientProfile $client,
        CareService $service,
        SocialHelperProfile $helper,
        string $status,
        ?Carbon $scheduledAt = null
    ): Order {
        $order = Order::factory()->create([
            'metadata' => [
                'service_type' => 'social_care_visit',
            ],
        ]);

        CareOrderDetails::create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper->id,
            'care_status' => $status,
            'scheduled_start_at' => $scheduledAt ?? now()->addDay(),
            'scheduled_end_at' => ($scheduledAt ?? now()->addDay())->addHour(),
        ]);

        return $order->fresh('careDetails');
    }
}
