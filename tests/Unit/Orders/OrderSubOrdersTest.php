<?php

namespace Tests\Unit\Orders;

use App\Enums\ServiceType;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderLinkingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSubOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sub_order_inherits_user_and_sets_parent_id(): void
    {
        $user = User::factory()->create();
        $parentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::GROCERY_DELIVERY->value,
        ]);

        $subOrder = $parentOrder->createSubOrder([
            'service_type' => ServiceType::HANDYMAN_FIXED->value,
            'status' => 'pending_review',
        ]);

        $this->assertEquals($parentOrder->id, $subOrder->parent_order_id);
        $this->assertEquals($user->id, $subOrder->user_id);
        $this->assertEquals(ServiceType::HANDYMAN_FIXED->value, $subOrder->service_type);
    }

    public function test_linking_service_creates_handyman_sub_order_with_correct_type(): void
    {
        $user = User::factory()->create();
        $parentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::GROCERY_DELIVERY->value,
        ]);

        $linker = new OrderLinkingService;
        $subOrder = $linker->createHandymanSubOrder(
            $parentOrder,
            ServiceType::HANDYMAN_HOURLY->value
        );

        $this->assertEquals($parentOrder->id, $subOrder->parent_order_id);
        $this->assertEquals(ServiceType::HANDYMAN_HOURLY->value, $subOrder->service_type);
        $this->assertEquals('pending_review', $subOrder->status);
    }

    public function test_linking_service_creates_eco_sub_order_from_complex_repair(): void
    {
        $user = User::factory()->create();
        $parentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::COMPLEX_REPAIR->value,
        ]);

        $linker = new OrderLinkingService;
        $subOrder = $linker->createEcoSubOrder($parentOrder);

        $this->assertEquals($parentOrder->id, $subOrder->parent_order_id);
        $this->assertEquals(ServiceType::ECO_DISPOSAL->value, $subOrder->service_type);
        $this->assertEquals('pending_review', $subOrder->status);
    }

    public function test_sub_order_can_override_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $parentOrder = Order::factory()->create([
            'user_id' => $user1->id,
        ]);

        $subOrder = $parentOrder->createSubOrder([
            'user_id' => $user2->id,
        ]);

        $this->assertEquals($user2->id, $subOrder->user_id);
    }
}
