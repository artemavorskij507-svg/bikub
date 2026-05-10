<?php

namespace Tests\Feature\Account;

use App\Enums\ServiceType;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\OrderCareContext;
use App\Models\Store;
use App\Models\TrustedContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class NewOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    public function test_new_order_index_requires_auth(): void
    {
        $this->get('/account/new')->assertRedirect('/login');
    }

    public function test_care_form_forbidden_without_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->get('/account/new/care')
            ->assertStatus(403);
    }

    public function test_user_can_create_care_order_only_for_related_client_profile(): void
    {
        $user = User::factory()->create();
        $allowedClient = ClientProfile::factory()->create();
        TrustedContact::factory()->create([
            'client_profile_id' => $allowedClient->id,
            'user_id' => $user->id,
            'email' => 'trusted@example.com',
        ]);

        $forbiddenClient = ClientProfile::factory()->create();

        $careService = CareService::factory()->create([
            'is_active' => true,
            'base_duration_minutes' => 60,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->post('/account/new/care', [
                'client_profile_id' => $allowedClient->id,
                'care_service_id' => $careService->id,
                'scheduled_start_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'duration_minutes' => 90,
                'comment' => 'Проверка',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->post('/account/new/care', [
                'client_profile_id' => $forbiddenClient->id,
                'care_service_id' => $careService->id,
                'scheduled_start_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertStatus(403);
    }

    public function test_delivery_order_from_account_creates_order_for_user(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->withSession(['current_zone_id' => 1])
            ->post('/account/new/delivery', [
                'store_id' => $store->id,
                'address' => 'Oslo, Karl Johans gate 1',
                'comment' => 'Привезите к подъезду',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_type' => ServiceType::GROCERY_DELIVERY->value,
        ]);
    }

    public function test_delivery_order_creates_care_context_for_active_client(): void
    {
        $user = User::factory()->create();
        $client = ClientProfile::factory()->create(['user_id' => $user->id]);
        $store = Store::factory()->create();

        session([
            'current_zone_id' => 1,
            'account.active_client_profile_id' => $client->id,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->withSession([
                'current_zone_id' => 1,
                'account.active_client_profile_id' => $client->id,
            ])
            ->post('/account/new/delivery', [
                'store_id' => $store->id,
                'address' => 'Oslo, Pilestredet 1',
                'comment' => 'Для клиента',
            ])
            ->assertRedirect();

        $order = Order::where('service_type', ServiceType::GROCERY_DELIVERY->value)->firstOrFail();

        $this->assertTrue(
            OrderCareContext::where('order_id', $order->id)
                ->where('client_profile_id', $client->id)
                ->exists()
        );
    }
}
