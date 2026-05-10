<?php

namespace Tests\Feature\Account;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_summary_calculates_totals_for_user(): void
    {
        $user = User::factory()->create();

        PaymentTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'charge',
            'amount_minor' => 10000,
        ]);
        PaymentTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'refund',
            'amount_minor' => -2000,
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->get('/account/billing')
            ->assertOk()
            ->assertSee('Всего оплачено');
    }

    public function test_transactions_list_shows_only_user_transactions(): void
    {
        $user = User::factory()->create();
        PaymentTransaction::factory()->count(3)->create(['user_id' => $user->id]);
        PaymentTransaction::factory()->create(); // foreign

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->get('/account/billing/transactions')
            ->assertOk()
            ->assertSee('История платежей');
    }
}
