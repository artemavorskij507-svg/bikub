<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\OrderCareContext;
use App\Models\TrustedContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ClientContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    public function test_switcher_shows_only_related_clients(): void
    {
        $user = User::factory()->create();
        session(['current_zone_id' => 1]);

        $selfClient = ClientProfile::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Моя учетная запись',
        ]);

        $trustedClient = ClientProfile::factory()->create([
            'full_name' => 'Бабушка Мария',
        ]);

        TrustedContact::factory()->create([
            'client_profile_id' => $trustedClient->id,
            'user_id' => $user->id,
            'full_name' => 'Контакт',
            'email' => 'contact@example.com',
        ]);

        $foreignClient = ClientProfile::factory()->create([
            'full_name' => 'Чужой клиент',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->get('/account');

        $response->assertOk();
        $response->assertSee($selfClient->full_name);
        $response->assertSee($trustedClient->full_name);
        $response->assertDontSee($foreignClient->full_name);
    }

    public function test_user_cannot_switch_to_unrelated_client(): void
    {
        $user = User::factory()->create();
        $unrelatedClient = ClientProfile::factory()->create();
        session(['current_zone_id' => 1]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->post('/account/switch-client', ['client_profile_id' => $unrelatedClient->id])
            ->assertStatus(403);
    }

    public function test_active_client_filters_orders(): void
    {
        $user = User::factory()->create();
        session(['current_zone_id' => 1]);

        $selfClient = ClientProfile::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Я сам',
        ]);

        $parentClient = ClientProfile::factory()->create([
            'full_name' => 'Мама',
        ]);

        TrustedContact::factory()->create([
            'client_profile_id' => $parentClient->id,
            'user_id' => $user->id,
            'email' => 'parent@example.com',
        ]);

        $selfOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => 'social_care_visit',
            'status' => 'pending',
        ]);

        OrderCareContext::create([
            'order_id' => $selfOrder->id,
            'client_profile_id' => $selfClient->id,
            'is_vulnerable_client' => true,
            'needs_extra_care' => true,
        ]);

        $parentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => 'social_care_visit',
            'status' => 'pending',
        ]);

        OrderCareContext::create([
            'order_id' => $parentOrder->id,
            'client_profile_id' => $parentClient->id,
            'is_vulnerable_client' => true,
            'needs_extra_care' => true,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->post('/account/switch-client', ['client_profile_id' => $parentClient->id])
            ->assertRedirect();

        $response = $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->get('/account/orders');

        $response->assertOk();
        $response->assertSee($parentOrder->order_number);
        $response->assertDontSee($selfOrder->order_number);
    }
}
