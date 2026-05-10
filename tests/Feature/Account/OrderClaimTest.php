<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_claim_for_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('account.orders.claim.store', $order), [
            'type' => 'quality',
            'severity' => 'high',
            'title' => 'Плохое качество работ',
            'description' => 'Мастер выполнил работу некачественно, требуется переделка.',
        ]);

        $response->assertRedirect(route('account.orders.show', $order));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('claims', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'quality',
            'severity' => 'high',
            'title' => 'Плохое качество работ',
            'status' => 'open',
        ]);
    }

    public function test_user_cannot_submit_claim_for_someone_else_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->post(route('account.orders.claim.store', $order), [
            'type' => 'quality',
            'title' => 'Test',
            'description' => 'Test description',
        ]);

        $response->assertStatus(403);
    }

    public function test_claim_requires_all_fields(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('account.orders.claim.store', $order), []);

        $response->assertSessionHasErrors(['type', 'title', 'description']);
    }

    public function test_user_can_view_claim_form(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('account.orders.claim.create', $order));

        $response->assertStatus(200);
        $response->assertSee('Оставить претензию');
    }
}
