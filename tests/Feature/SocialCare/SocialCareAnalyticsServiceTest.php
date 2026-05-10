<?php

namespace Tests\Feature\SocialCare;

use App\Enums\CareOrderStatus;
use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use App\Models\User;
use App\Models\VisitReport;
use App\Services\SocialCare\SocialCareAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCareAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SocialCareAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SocialCareAnalyticsService::class);
    }

    /** @test */
    public function aggregate_kpi_counts_completed_visits_only(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();
        $helper = SocialHelperProfile::factory()->create();
        $service = CareService::factory()->create(['base_duration_minutes' => 60]);

        $from = now()->subDays(7);
        $to = now();

        // Create completed visit
        $completedOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'metadata' => ['service_type' => 'social_care_visit'],
        ]);

        $completedDetails = CareOrderDetails::factory()->create([
            'order_id' => $completedOrder->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDay(),
            'scheduled_end_at' => $from->copy()->addDay()->addHour(),
        ]);

        VisitReport::factory()->create([
            'care_order_details_id' => $completedDetails->id,
            'helper_profile_id' => $helper->id,
            'started_at' => $from->copy()->addDay(),
            'ended_at' => $from->copy()->addDay()->addHour(),
            'status' => 'COMPLETED',
        ]);

        // Create pending visit (should not be counted)
        $pendingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'metadata' => ['service_type' => 'social_care_visit'],
        ]);

        CareOrderDetails::factory()->create([
            'order_id' => $pendingOrder->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $service->id,
            'care_status' => CareOrderStatus::SCHEDULED->value,
            'scheduled_start_at' => $from->copy()->addDays(2),
        ]);

        $kpi = $this->service->aggregateKpi($from, $to);

        $this->assertEquals(1, $kpi['total_visits']);
        $this->assertEquals(1, $kpi['unique_clients']);
        $this->assertGreaterThan(0, $kpi['total_hours']);
    }

    /** @test */
    public function helpers_load_groups_by_helper_and_sums_hours(): void
    {
        $user = User::factory()->create();
        $client1 = ClientProfile::factory()->create();
        $client2 = ClientProfile::factory()->create();
        $helper1 = SocialHelperProfile::factory()->create(['level' => 'SOCIAL_HELPER']);
        $helper2 = SocialHelperProfile::factory()->create(['level' => 'COMMUNITY_PARTNER']);
        $service = CareService::factory()->create(['base_duration_minutes' => 60]);

        $from = now()->subDays(7);
        $to = now();

        // Helper 1: 2 visits
        $order1 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        $details1 = CareOrderDetails::factory()->create([
            'order_id' => $order1->id,
            'client_profile_id' => $client1->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper1->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDay(),
            'scheduled_end_at' => $from->copy()->addDay()->addHour(),
        ]);

        $order2 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        $details2 = CareOrderDetails::factory()->create([
            'order_id' => $order2->id,
            'client_profile_id' => $client2->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper1->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDays(2),
            'scheduled_end_at' => $from->copy()->addDays(2)->addHour(),
        ]);

        // Helper 2: 1 visit
        $order3 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        $details3 = CareOrderDetails::factory()->create([
            'order_id' => $order3->id,
            'client_profile_id' => $client1->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper2->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDays(3),
            'scheduled_end_at' => $from->copy()->addDays(3)->addHour(),
        ]);

        $helpers = $this->service->helpersLoad($from, $to);

        $this->assertCount(2, $helpers);

        $helper1Data = $helpers->firstWhere('helper_id', $helper1->id);
        $this->assertNotNull($helper1Data);
        $this->assertEquals(2, $helper1Data['visits_count']);
        $this->assertGreaterThan(0, $helper1Data['total_hours']);

        $helper2Data = $helpers->firstWhere('helper_id', $helper2->id);
        $this->assertNotNull($helper2Data);
        $this->assertEquals(1, $helper2Data['visits_count']);
        $this->assertGreaterThan(0, $helper2Data['volunteer_hours']); // Community Partner = volunteer
    }

    /** @test */
    public function clients_coverage_counts_unique_clients(): void
    {
        $user = User::factory()->create();
        $client1 = ClientProfile::factory()->create(['city' => 'Oslo']);
        $client2 = ClientProfile::factory()->create(['city' => 'Bergen']);
        $helper = SocialHelperProfile::factory()->create();
        $service = CareService::factory()->create(['base_duration_minutes' => 60]);

        $from = now()->subDays(7);
        $to = now();

        // Client 1: 2 visits
        $order1 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        CareOrderDetails::factory()->create([
            'order_id' => $order1->id,
            'client_profile_id' => $client1->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDay(),
        ]);

        $order2 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        CareOrderDetails::factory()->create([
            'order_id' => $order2->id,
            'client_profile_id' => $client1->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDays(2),
        ]);

        // Client 2: 1 visit
        $order3 = Order::factory()->create(['user_id' => $user->id, 'metadata' => ['service_type' => 'social_care_visit']]);
        CareOrderDetails::factory()->create([
            'order_id' => $order3->id,
            'client_profile_id' => $client2->id,
            'care_service_id' => $service->id,
            'assigned_helper_id' => $helper->id,
            'care_status' => CareOrderStatus::COMPLETED->value,
            'scheduled_start_at' => $from->copy()->addDays(3),
        ]);

        $clients = $this->service->clientsCoverage($from, $to);

        $this->assertCount(2, $clients);

        $client1Data = $clients->firstWhere('client_id', $client1->id);
        $this->assertNotNull($client1Data);
        $this->assertEquals(2, $client1Data['visits_count']);

        $client2Data = $clients->firstWhere('client_id', $client2->id);
        $this->assertNotNull($client2Data);
        $this->assertEquals(1, $client2Data['visits_count']);
    }
}
