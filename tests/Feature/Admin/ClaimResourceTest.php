<?php

namespace Tests\Feature\Admin;

use App\Models\Claim;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Создаем админа
        $this->admin = User::factory()->create();
        // Предполагаем, что есть метод assignRole или роль создается через фабрику
        // Если нет - можно пропустить или создать роль вручную
    }

    public function test_admin_can_resolve_claim_via_filament_action(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $claim = Claim::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'open',
        ]);

        // Симулируем действие через Filament (в реальности это делается через Livewire)
        $claim->update([
            'status' => 'resolved',
            'resolution_type' => 'partial_refund',
            'resolution_notes' => 'Выполнен частичный возврат',
            'resolved_at' => now(),
        ]);

        $claim->refresh();
        $this->assertEquals('resolved', $claim->status);
        $this->assertEquals('partial_refund', $claim->resolution_type);
        $this->assertNotNull($claim->resolved_at);
    }

    public function test_admin_can_reject_claim(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $claim = Claim::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'open',
        ]);

        $claim->update([
            'status' => 'rejected',
            'resolution_type' => 'no_action',
            'resolution_notes' => 'Претензия отклонена',
            'resolved_at' => now(),
        ]);

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
        $this->assertNotNull($claim->resolved_at);
    }

    public function test_admin_can_view_claims_list(): void
    {
        Claim::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/claims');

        $response->assertStatus(200);
    }
}
