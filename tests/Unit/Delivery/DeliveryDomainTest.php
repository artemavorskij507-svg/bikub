<?php

namespace Tests\Unit\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\GroceryItem;
use App\Models\Delivery\GroceryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryDomainTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_casts_delivery_type_and_tracking_status_enums()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $deliveryOrder = DeliveryOrder::create([
            'order_id' => $order->id,
            'type' => DeliveryType::GROCERY->value,
            'tracking_status' => DeliveryTrackingStatus::PENDING->value,
            'pickup_location' => ['lat' => 68.4385, 'lng' => 17.4275],
            'delivery_location' => ['lat' => 68.4400, 'lng' => 17.4300],
        ]);

        $this->assertInstanceOf(DeliveryType::class, $deliveryOrder->type);
        $this->assertEquals(DeliveryType::GROCERY, $deliveryOrder->type);

        $this->assertInstanceOf(DeliveryTrackingStatus::class, $deliveryOrder->tracking_status);
        $this->assertEquals(DeliveryTrackingStatus::PENDING, $deliveryOrder->tracking_status);
    }

    /** @test */
    public function it_casts_substitution_policy_in_grocery_order()
    {
        $groceryOrder = GroceryOrder::create([
            'substitution_policy' => SubstitutionPolicy::AI->value,
            'is_urgent' => false,
        ]);

        $this->assertInstanceOf(SubstitutionPolicy::class, $groceryOrder->substitution_policy);
        $this->assertEquals(SubstitutionPolicy::AI, $groceryOrder->substitution_policy);
    }

    /** @test */
    public function it_casts_substitution_policy_in_grocery_item()
    {
        $groceryOrder = GroceryOrder::factory()->create();

        $item = GroceryItem::create([
            'grocery_order_id' => $groceryOrder->id,
            'substitution_policy' => SubstitutionPolicy::CONTACT->value,
            'quantity' => 2,
            'unit_price' => 50.00,
            'total_price' => 100.00,
        ]);

        $this->assertInstanceOf(SubstitutionPolicy::class, $item->substitution_policy);
        $this->assertEquals(SubstitutionPolicy::CONTACT, $item->substitution_policy);
    }

    /** @test */
    public function scope_active_returns_only_active_deliveries()
    {
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);
        $order3 = Order::factory()->create(['user_id' => $user->id]);

        DeliveryOrder::create([
            'order_id' => $order1->id,
            'type' => DeliveryType::GROCERY->value,
            'tracking_status' => DeliveryTrackingStatus::PENDING->value,
        ]);

        DeliveryOrder::create([
            'order_id' => $order2->id,
            'type' => DeliveryType::BULKY->value,
            'tracking_status' => DeliveryTrackingStatus::IN_TRANSIT->value,
        ]);

        DeliveryOrder::create([
            'order_id' => $order3->id,
            'type' => DeliveryType::FOOD->value,
            'tracking_status' => DeliveryTrackingStatus::DELIVERED->value,
        ]);

        $active = DeliveryOrder::active()->get();

        $this->assertCount(2, $active);
        $this->assertTrue($active->contains('tracking_status', DeliveryTrackingStatus::PENDING));
        $this->assertTrue($active->contains('tracking_status', DeliveryTrackingStatus::IN_TRANSIT));
        $this->assertFalse($active->contains('tracking_status', DeliveryTrackingStatus::DELIVERED));
    }

    /** @test */
    public function scope_of_type_filters_by_delivery_type()
    {
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);

        DeliveryOrder::create([
            'order_id' => $order1->id,
            'type' => DeliveryType::GROCERY->value,
            'tracking_status' => DeliveryTrackingStatus::PENDING->value,
        ]);

        DeliveryOrder::create([
            'order_id' => $order2->id,
            'type' => DeliveryType::BULKY->value,
            'tracking_status' => DeliveryTrackingStatus::PENDING->value,
        ]);

        $groceryOrders = DeliveryOrder::ofType(DeliveryType::GROCERY)->get();
        $this->assertCount(1, $groceryOrders);
        $this->assertEquals(DeliveryType::GROCERY, $groceryOrders->first()->type);

        // Test with string
        $bulkyOrders = DeliveryOrder::ofType('bulky')->get();
        $this->assertCount(1, $bulkyOrders);
        $this->assertEquals(DeliveryType::BULKY, $bulkyOrders->first()->type);
    }
}
