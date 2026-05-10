<?php

namespace Tests\Feature\Care;

use App\Models\CareOrderDetails;
use App\Models\CarePlan;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\TrustedContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareAccountTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dashboard_shows_clients_for_trusted_contact(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClientProfile();
        $service = $this->makeCareService();

        TrustedContact::create([
            'client_profile_id' => $client->id,
            'user_id' => $user->id,
            'full_name' => 'Guardian',
            'relationship' => 'son',
            'phone' => '+4712345678',
            'email' => 'guardian@example.com',
            'can_manage_orders' => true,
            'can_view_reports' => true,
            'is_primary' => true,
        ]);

        CarePlan::create([
            'client_profile_id' => $client->id,
            'care_service_id' => $service->id,
            'frequency' => 'WEEKLY',
            'duration_minutes' => 60,
            'starts_at' => now()->subWeek(),
            'status' => 'ACTIVE',
        ]);

        $this->createCareOrder($client, $service);

        $response = $this->actingAs($user)->get(route('care.dashboard'));

        $response->assertOk();
        $response->assertSee($client->full_name);
    }

    /** @test */
    public function client_cannot_access_other_clients_profile(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $clientA = $this->makeClientProfile(['user_id' => $userA->id, 'full_name' => 'Client A']);
        $clientB = $this->makeClientProfile(['user_id' => $userB->id, 'full_name' => 'Client B']);

        $response = $this->actingAs($userA)->get(route('care.clients.show', $clientB));
        $response->assertForbidden();

        $this->actingAs($userA)
            ->get(route('care.clients.show', $clientA))
            ->assertOk();
    }

    /** @test */
    public function trusted_contact_can_view_social_care_order(): void
    {
        $trustedUser = User::factory()->create();
        $client = $this->makeClientProfile();
        $service = $this->makeCareService();

        TrustedContact::create([
            'client_profile_id' => $client->id,
            'user_id' => $trustedUser->id,
            'full_name' => 'Helper',
            'relationship' => 'daughter',
            'phone' => '123',
            'email' => 'helper@example.com',
            'can_manage_orders' => true,
            'can_view_reports' => true,
            'is_primary' => true,
        ]);

        $order = $this->createCareOrder($client, $service);

        $this->actingAs($trustedUser)
            ->get(route('care.orders.show', $order))
            ->assertOk()
            ->assertSee($client->full_name);
    }

    /** @test */
    public function trusted_contact_can_cancel_future_visit(): void
    {
        $trustedUser = User::factory()->create();
        $client = $this->makeClientProfile();
        $service = $this->makeCareService();

        TrustedContact::create([
            'client_profile_id' => $client->id,
            'user_id' => $trustedUser->id,
            'full_name' => 'Sibling',
            'relationship' => 'sister',
            'phone' => '123',
            'email' => 'sister@example.com',
            'can_manage_orders' => true,
            'can_view_reports' => true,
            'is_primary' => true,
        ]);

        $order = $this->createCareOrder($client, $service, [
            'care_status' => 'SCHEDULED',
            'scheduled_start_at' => now()->addDays(2),
        ]);

        $this->actingAs($trustedUser)
            ->post(route('care.orders.cancel', $order), ['reason' => 'Need to reschedule'])
            ->assertRedirect(route('care.orders.show', $order));

        $this->assertDatabaseHas('care_order_details', [
            'order_id' => $order->id,
            'care_status' => 'CANCELLED_BY_TRUSTED_CONTACT',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    private function makeClientProfile(array $overrides = []): ClientProfile
    {
        return ClientProfile::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Test Client '.uniqid(),
            'phone' => '+47'.random_int(10000000, 99999999),
            'email' => uniqid('client_').'@example.com',
            'address_line' => 'Central street 1',
            'postal_code' => '8514',
            'city' => 'Narvik',
            'is_active' => true,
        ], $overrides));
    }

    private function makeCareService(): CareService
    {
        return CareService::create([
            'code' => 'care-'.uniqid(),
            'name' => 'Комплексная забота',
            'required_level' => 'SOCIAL_HELPER',
            'base_duration_minutes' => 60,
            'base_price_nok' => 750,
            'is_recurring_available' => true,
            'is_active' => true,
        ]);
    }

    private function createCareOrder(ClientProfile $client, CareService $service, array $detailOverrides = []): Order
    {
        $order = Order::factory()->create([
            'metadata' => [
                'service_type' => 'social_care_visit',
            ],
        ]);

        CareOrderDetails::create(array_merge([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $service->id,
            'care_status' => 'SCHEDULED',
            'scheduled_start_at' => Carbon::now()->addDay(),
            'scheduled_end_at' => Carbon::now()->addDay()->addHour(),
        ], $detailOverrides));

        return $order->fresh('careDetails');
    }
}
