<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\Account\AccountReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_orders_belonging_to_user(): void
    {
        $service = app(AccountReadService::class);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Order::factory()->count(2)->create([
            'user_id' => $user->id,
            'service_type' => 'eco_disposal',
        ]);

        Order::factory()->create([
            'user_id' => $otherUser->id,
            'service_type' => 'eco_disposal',
        ]);

        $paginator = $service->getPaginatedOrdersForUser($user);

        $this->assertCount(2, $paginator->items());
        $this->assertTrue(
            collect($paginator->items())->every(fn (Order $order) => $order->user_id === $user->id)
        );
    }
}
