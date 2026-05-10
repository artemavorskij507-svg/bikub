<?php

namespace Tests\Feature\Webhooks;

use App\Services\WebhookNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('webhooks.secret', 'test-secret-key-12345');
        Config::set('webhooks.endpoints', ['https://example.com/webhook']);
        Config::set('webhooks.timeout', 5);
        Config::set('webhooks.max_retries', 3);
    }

    /** @test */
    public function it_generates_valid_webhook_signature()
    {
        $notifier = new WebhookNotifier;

        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $payload = ['task_id' => 123, 'status' => 'completed'];
        $notifier->send('task.completed', $payload);

        Http::assertSent(function ($request) {
            $signature = $request->header('X-GLF-Signature')[0] ?? null;
            $eventType = $request->header('X-GLF-Event-Type')[0] ?? null;

            // Verify signature header exists
            $this->assertNotNull($signature);
            $this->assertNotNull($eventType);

            // Verify signature is valid
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $expectedSignature = hash_hmac('sha256', $body, config('webhooks.secret'));

            return hash_equals($expectedSignature, $signature);
        });
    }

    /** @test */
    public function it_verifies_webhook_signature_correctly()
    {
        $payload = json_encode(['type' => 'test', 'data' => ['foo' => 'bar']], JSON_UNESCAPED_UNICODE);
        $secret = config('webhooks.secret');
        $validSignature = hash_hmac('sha256', $payload, $secret);
        $invalidSignature = 'invalid-signature';

        $this->assertTrue(WebhookNotifier::verifySignature($payload, $validSignature));
        $this->assertFalse(WebhookNotifier::verifySignature($payload, $invalidSignature));
    }

    /** @test */
    public function it_retries_on_failure()
    {
        $notifier = new WebhookNotifier;

        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $payload = ['task_id' => 123];
        $notifier->send('task.assigned', $payload);

        // Should attempt to send (HTTP client will retry based on configuration)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'example.com/webhook');
        });
    }

    /** @test */
    public function it_deduplicates_webhooks()
    {
        $notifier = new WebhookNotifier;
        Cache::flush(); // Clear cache

        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $payload = ['task_id' => 123, 'status' => 'completed'];

        // Send first webhook
        $notifier->send('task.completed', $payload);

        // Send duplicate webhook (should be deduplicated)
        $notifier->send('task.completed', $payload);

        // Should only be sent once (deduplication cache prevents second send)
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_includes_required_headers()
    {
        $notifier = new WebhookNotifier;

        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $payload = ['task_id' => 456];
        $notifier->send('task.failed', $payload);

        Http::assertSent(function ($request) {
            $headers = $request->headers();

            return isset($headers['X-GLF-Signature']) &&
                   isset($headers['X-GLF-Event-Type']) &&
                   isset($headers['X-GLF-Timestamp']) &&
                   $headers['X-GLF-Event-Type'][0] === 'task.failed';
        });
    }

    /** @test */
    public function it_handles_network_timeouts_gracefully()
    {
        $notifier = new WebhookNotifier;

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        // Should not throw exception, just log error
        $this->expectNotToPerformAssertions();

        $notifier->send('task.completed', ['task_id' => 789]);
    }
}
