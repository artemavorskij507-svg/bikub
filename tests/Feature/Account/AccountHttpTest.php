<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\SocialCareNotificationSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AccountHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_dashboard_shows_only_user_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Session::start();
        $this->withSession(['current_zone_id' => 1]);

        $userOrder = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => 'eco_disposal',
            'status' => 'pending',
        ]);

        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'service_type' => 'handyman',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertViewHas('orderCards', function ($cards) use ($userOrder, $otherOrder) {
            return $cards->contains(fn ($card) => $card['id'] === $userOrder->id)
                && $cards->doesntContain(fn ($card) => $card['id'] === $otherOrder->id);
        });
    }

    public function test_account_orders_show_forbidden_for_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Session::start();
        $this->withSession(['current_zone_id' => 1]);

        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'service_type' => 'eco_disposal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get("/account/orders/{$order->id}");

        $response->assertForbidden();
    }

    public function test_social_care_tab_returns_404_when_no_care_relations(): void
    {
        $user = User::factory()->create();
        Session::start();
        $this->withSession(['current_zone_id' => 1]);

        $response = $this->actingAs($user)->get('/account/care');

        $response->assertNotFound();
    }

    public function test_notifications_can_be_updated_and_affect_user_settings(): void
    {
        $user = User::factory()->create();

        Session::start();
        $this->withSession(['current_zone_id' => 1]);

        $response = $this->actingAs($user)->post('/account/notifications', [
            '_token' => Session::token(),
            'notify_care_order_created' => '1',
            'notify_care_plan_created' => '0',
            'notify_visit_status_changes' => '1',
            'notify_visit_reports' => '1',
            'notify_emergency' => '0',
            'notify_reschedule_requests' => '1',
        ]);

        $response->assertRedirect(route('account.notifications.edit'));
        $response->assertSessionHas('status');

        $settings = SocialCareNotificationSettings::where('user_id', $user->id)->first();

        $this->assertNotNull($settings);
        $this->assertTrue($settings->notify_care_order_created);
        $this->assertFalse($settings->notify_care_plan_created);
        $this->assertTrue($settings->notify_visit_status_changes);
        $this->assertTrue($settings->notify_visit_reports);
        $this->assertFalse($settings->notify_emergency);
        $this->assertTrue($settings->notify_reschedule_requests);
    }
}
