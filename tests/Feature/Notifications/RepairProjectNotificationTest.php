<?php

namespace Tests\Feature\Notifications;

use App\Events\RepairProjectCreated;
use App\Models\Order;
use App\Models\RepairProject;
use App\Models\User;
use App\Notifications\RepairProjectCreatedForCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RepairProjectNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_project_created_notifies_customer_and_emits_webhook(): void
    {
        Notification::fake();
        Http::fake();

        Config::set('services.n8n.enabled', true);
        Config::set('services.n8n.webhook_url', 'https://example.test/webhook');

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);
        $project = RepairProject::factory()->create([
            'order_id' => $order->id,
        ]);

        event(new RepairProjectCreated($project));

        Notification::assertSentTo($user, RepairProjectCreatedForCustomer::class);

        Http::assertSent(function ($request) use ($project, $order) {
            return $request->url() === 'https://example.test/webhook'
                && $request['event'] === 'repair.project_created'
                && (int) $request['project_id'] === $project->id
                && (int) $request['order_id'] === $order->id;
        });
    }
}
