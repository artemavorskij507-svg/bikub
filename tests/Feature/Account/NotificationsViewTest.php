<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsViewTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsWith2fa(User $user)
    {
        return $this->actingAs($user)->withSession(['two_factor_passed_at' => now()]);
    }

    public function test_user_can_view_notifications_list(): void
    {
        $user = User::factory()->create();
        DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'test.notification',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => [
                'type' => 'handyman.order_created',
                'order_id' => 123,
            ],
        ]);

        $response = $this->actingAsWith2fa($user)->get(route('account.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Центр уведомлений');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = DatabaseNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => 'test.notification',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => ['type' => 'handyman.order_created'],
        ]);

        $this->actingAsWith2fa($user)
            ->post(route('account.notifications.read', $notification));

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }
}
