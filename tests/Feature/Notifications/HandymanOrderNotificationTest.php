<?php

namespace Tests\Feature\Notifications;

use App\Events\HandymanOrderRequested;
use App\Models\Order;
use App\Models\User;
use App\Notifications\HandymanOrderCreatedForCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HandymanOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handyman_order_requested_notifies_customer(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        event(new HandymanOrderRequested($order));

        Notification::assertSentTo($user, HandymanOrderCreatedForCustomer::class);
    }
}
