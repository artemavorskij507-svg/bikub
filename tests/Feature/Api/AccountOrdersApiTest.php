<?php

namespace Tests\Feature\Api;

use App\Enums\ServiceType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_list_returns_only_user_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->create(); // foreign order

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_orders_show_forbidden_for_foreign_order(): void
    {
        $user = User::factory()->create();
        $foreignOrder = Order::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/account/orders/{$foreignOrder->id}")
            ->assertForbidden();
    }

    public function test_orders_show_returns_data_for_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::GROCERY_DELIVERY->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/account/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $order->id);
    }
}
