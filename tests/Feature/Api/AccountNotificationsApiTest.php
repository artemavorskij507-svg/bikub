<?php

namespace Tests\Feature\Api;

use App\Models\NotificationFeed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountNotificationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_returns_data(): void
    {
        $user = User::factory()->create();
        NotificationFeed::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/notifications/list')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_mark_read_endpoint_updates_notification(): void
    {
        $user = User::factory()->create();
        $notification = NotificationFeed::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/account/notifications/mark-read', ['id' => $notification->id])
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_timeline_endpoint_returns_events(): void
    {
        $user = User::factory()->create();
        NotificationFeed::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/timeline?limit=5')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
