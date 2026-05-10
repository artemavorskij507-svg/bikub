<?php

namespace Tests\Feature\Account;

use App\Models\Delivery\DeliveryOrder;
use App\Models\Delivery\GroceryOrder;
use App\Models\Order;
use App\Models\RetailStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_see_delivery_orders_in_account()
    {
        $user = User::factory()->create();
        $store = RetailStore::factory()->create([
            'is_active' => true,
            'supports_grocery_delivery' => true,
        ]);

        // Create a delivery order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'service_type' => 'delivery',
        ]);

        $groceryOrder = GroceryOrder::factory()->create([
            'store_id' => $store->id,
        ]);

        $deliveryOrder = DeliveryOrder::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => GroceryOrder::class,
            'orderable_id' => $groceryOrder->id,
        ]);

        // Check orders list
        $response = $this->actingAs($user)
            ->get('/account/orders');

        $response->assertStatus(200)
            ->assertSee((string) $order->id)
            ->assertSee($deliveryOrder->delivery_address ?? '');
    }

    /** @test */
    public function customer_can_view_delivery_order_details()
    {
        $user = User::factory()->create();
        $store = RetailStore::factory()->create([
            'is_active' => true,
            'supports_grocery_delivery' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'service_type' => 'delivery',
        ]);

        $groceryOrder = GroceryOrder::factory()->create([
            'store_id' => $store->id,
        ]);

        $deliveryOrder = DeliveryOrder::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => GroceryOrder::class,
            'orderable_id' => $groceryOrder->id,
            'pickup_address' => 'Store Address',
            'delivery_address' => 'Customer Address',
        ]);

        $response = $this->actingAs($user)
            ->get("/account/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertSee('Store Address')
            ->assertSee('Customer Address');
    }

    /** @test */
    public function customer_cannot_see_other_users_orders()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user1->id,
        ]);

        $response = $this->actingAs($user2)
            ->get("/account/orders/{$order->id}");

        $response->assertStatus(403);
    }
}
