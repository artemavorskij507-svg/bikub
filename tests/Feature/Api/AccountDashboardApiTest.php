<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_auth(): void
    {
        $this->getJson('/api/v1/account/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_returns_orders_and_kpi_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/account/dashboard')
            ->assertOk()
            ->json('data');

        $this->assertArrayHasKey('orders', $response);
        $this->assertIsArray($response['orders']);
        $this->assertArrayHasKey('kpi', $response);
    }
}
