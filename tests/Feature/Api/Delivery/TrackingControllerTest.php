<?php

namespace Tests\Feature\Api\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_tracking_without_token(): void
    {
        $deliveryOrder = DeliveryOrder::factory()->create();

        $this->getJson("/api/v1/delivery/orders/{$deliveryOrder->id}/tracking")
            ->assertStatus(403);
    }

    public function test_guest_can_view_tracking_with_valid_token(): void
    {
        $deliveryOrder = DeliveryOrder::factory()->create();

        $this->getJson("/api/v1/delivery/orders/{$deliveryOrder->id}/tracking?tracking_token={$deliveryOrder->tracking_token}")
            ->assertOk()
            ->assertJsonPath('data.delivery_order_id', $deliveryOrder->id);
    }

    public function test_order_owner_can_view_tracking_without_token(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $deliveryOrder = DeliveryOrder::factory()->for($order)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/delivery/orders/{$deliveryOrder->id}/tracking")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id);
    }

    public function test_update_status_requires_authentication(): void
    {
        $deliveryOrder = DeliveryOrder::factory()->create();

        $this->patchJson("/api/v1/delivery/orders/{$deliveryOrder->id}/status", [
            'tracking_status' => DeliveryTrackingStatus::IN_TRANSIT->value,
        ])->assertStatus(401);
    }

    public function test_courier_can_update_status(): void
    {
        $courier = User::factory()->create();
        $deliveryOrder = DeliveryOrder::factory()->create([
            'courier_id' => $courier->id,
        ]);

        $this->actingAs($courier, 'sanctum')
            ->patchJson("/api/v1/delivery/orders/{$deliveryOrder->id}/status", [
                'tracking_status' => DeliveryTrackingStatus::IN_TRANSIT->value,
                'courier_location' => [
                    'lat' => 68.44,
                    'lng' => 17.43,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.tracking_status', DeliveryTrackingStatus::IN_TRANSIT->value);
    }
}
