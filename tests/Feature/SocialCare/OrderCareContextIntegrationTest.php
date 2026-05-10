<?php

namespace Tests\Feature\SocialCare;

use App\Enums\ServiceType;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\TrustedContact;
use App\Models\User;
use App\Services\SocialCare\SocialCareIntegrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCareContextIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function order_care_context_created_for_vulnerable_delivery_order(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();

        // Create a delivery order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'metadata' => ['service_type' => 'grocery_delivery'],
        ]);

        $integrationService = app(SocialCareIntegrationService::class);

        $context = $integrationService->ensureCareContextForOrder(
            $order,
            $client,
            null,
            $user,
            'Клиент плохо слышит, нужна помощь с разбором покупок'
        );

        $this->assertDatabaseHas('order_care_contexts', [
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'is_vulnerable_client' => true,
            'needs_extra_care' => true,
        ]);

        $this->assertEquals($order->id, $context->order_id);
        $this->assertEquals($client->id, $context->client_profile_id);
    }

    /** @test */
    public function social_care_suborder_can_be_attached_to_delivery_order(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();
        $trustedContact = TrustedContact::factory()->create([
            'client_profile_id' => $client->id,
        ]);
        $careService = CareService::factory()->create();

        // Create a delivery order
        $deliveryOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'metadata' => ['service_type' => 'grocery_delivery'],
        ]);

        $integrationService = app(SocialCareIntegrationService::class);

        $socialCareOrder = $integrationService->attachSocialVisitToOrder(
            $deliveryOrder,
            $client,
            $trustedContact,
            $careService,
            Carbon::now()->addDay(),
            60,
            $user,
            'Помочь с разбором покупок'
        );

        $this->assertDatabaseHas('orders', [
            'id' => $socialCareOrder->id,
            'parent_order_id' => $deliveryOrder->id,
        ]);

        $this->assertDatabaseHas('care_order_details', [
            'order_id' => $socialCareOrder->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
        ]);

        $this->assertTrue($socialCareOrder->parentOrder->id === $deliveryOrder->id);
        $this->assertTrue($deliveryOrder->socialCareSubOrders()->exists());
    }

    /** @test */
    public function create_suborder_from_social_care_creates_correct_parent_child_relation(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();
        $careService = CareService::factory()->create();

        // Create a social care order
        $socialCareOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'metadata' => ['service_type' => 'social_care_visit'],
        ]);

        \App\Models\CareOrderDetails::factory()->create([
            'order_id' => $socialCareOrder->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
            'care_status' => 'SCHEDULED',
        ]);

        $integrationService = app(SocialCareIntegrationService::class);

        $ecoSubOrder = $integrationService->createSubOrderFromSocialCare(
            $socialCareOrder,
            ServiceType::ECO_DISPOSAL,
            ['description' => 'Вывоз старой мебели'],
            $user
        );

        $this->assertDatabaseHas('orders', [
            'id' => $ecoSubOrder->id,
            'parent_order_id' => $socialCareOrder->id,
        ]);

        $this->assertTrue($ecoSubOrder->parentOrder->id === $socialCareOrder->id);
        $this->assertTrue($socialCareOrder->subOrders()->exists());
    }

    /** @test */
    public function order_care_context_is_created_when_attaching_social_visit(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();
        $careService = CareService::factory()->create();

        $deliveryOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $integrationService = app(SocialCareIntegrationService::class);

        $socialCareOrder = $integrationService->attachSocialVisitToOrder(
            $deliveryOrder,
            $client,
            null,
            $careService,
            Carbon::now()->addDay(),
            null,
            $user
        );

        // Check that care context was created for parent order
        $this->assertDatabaseHas('order_care_contexts', [
            'order_id' => $deliveryOrder->id,
            'client_profile_id' => $client->id,
        ]);

        $deliveryOrder->refresh();
        $this->assertNotNull($deliveryOrder->careContext);
    }
}
