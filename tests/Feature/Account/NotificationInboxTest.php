<?php

namespace Tests\Feature\Account;

use App\Models\NotificationFeed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_lists_only_user_notifications(): void
    {
        $user = User::factory()->create();
        NotificationFeed::factory()->count(2)->create(['user_id' => $user->id]);
        NotificationFeed::factory()->create(); // other user

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->get('/account/notifications/inbox')
            ->assertOk()
            ->assertSee('Центр уведомлений')
            ->assertSee('Тестовое уведомление');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = NotificationFeed::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/notifications/mark-read', ['id' => $notification->id])
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        NotificationFeed::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/notifications/mark-all-read')
            ->assertRedirect();

        $this->assertEquals(
            0,
            NotificationFeed::where('user_id', $user->id)->whereNull('read_at')->count()
        );
    }
}
