<?php

namespace Tests\Feature\Api;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountBillingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_summary_endpoint_returns_totals(): void
    {
        $user = User::factory()->create();
        PaymentTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'charge',
            'amount_minor' => 15000,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/billing/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => ['currency', 'total_charged', 'net_total']]);
    }

    public function test_billing_transactions_endpoint_returns_list(): void
    {
        $user = User::factory()->create();
        PaymentTransaction::factory()->count(2)->create([
            'user_id' => $user->id,
            'type' => 'charge',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/billing/transactions')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
