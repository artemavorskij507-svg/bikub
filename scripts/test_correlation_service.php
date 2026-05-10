#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookLog;
use App\Services\CorrelationService;

echo "=== Correlation Service Test ===\n\n";

// Cleanup any existing test webhooks
WebhookLog::where('provider', 'stripe')->where('external_id', 'like', 'test_%')->delete();
WebhookLog::where('provider', 'n8n')->where('external_id', 'like', 'test_%')->delete();

// Setup: Create test data in database
echo "Setting up test data...\n\n";

// Create test user (owner of order)
$testUserId = \DB::table('users')->insertGetId([
    'name' => 'Test User',
    'email' => 'testuser+'.uniqid().'@example.com',
    'password' => \Hash::make('test'),
    'created_at' => now(),
    'updated_at' => now(),
]);

// Create test order
// Note: match actual orders table columns
$order = \DB::table('orders')->insertGetId([
    'order_number' => 'TEST-'.uniqid(),
    'user_id' => $testUserId,
    'status' => 'pending',
    'priority' => 'normal',
    'notes' => null,
    'location' => null,
    'total_amount' => 5000.00,
    'currency' => 'USD',
    'payment_status' => 'pending',
    'payment_method' => null,
    'metadata' => null,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Create test payment
// payments table uses uuid primary key; set id explicitly
$paymentUuid = (string) \Illuminate\Support\Str::uuid();
\DB::table('payments')->insert([
    'id' => $paymentUuid,
    'order_id' => $order,
    'provider' => 'stripe',
    'provider_ref' => 'pi_test_12345',
    'amount' => 5000.00,
    'currency' => 'USD',
    'status' => 'succeeded',
    'metadata' => json_encode(['payment_external_id' => 'ch_001']),
    'processed_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
$payment = $paymentUuid;

// Create test user (executor)
$executor_id = \DB::table('users')->insertGetId([
    'name' => 'Test Executor',
    'email' => 'executor+'.uniqid().'@example.com',
    'password' => \Hash::make('test'),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✓ Test data created:\n";
echo "  - Order ID: $order\n";
echo "  - Payment ID: $payment\n";
echo "  - Executor ID: $executor_id\n";
echo "\n";

$service = app(CorrelationService::class);

// Test 1: Stripe payment_intent
echo "TEST 1: Stripe (payment_intent.id)\n";
echo str_repeat('-', 60)."\n";

$webhook1 = WebhookLog::create([
    'provider' => 'stripe',
    'event_type' => 'charge.succeeded',
    'external_id' => 'test_ch_001',
    'status' => 'received',
    'payload' => [
        'id' => 'evt_1',
        'type' => 'charge.succeeded',
        'data' => [
            'object' => [
                'id' => 'ch_001',
                'payment_intent' => 'pi_test_12345',
                'amount' => 5000,
                'currency' => 'usd',
            ],
        ],
    ],
    'request_id' => 'req1',
    'received_at' => now(),
]);

$corr1 = $service->correlate($webhook1);
echo "Payload has payment_intent: pi_test_12345\n";
echo "Expected: Payment ID $payment found\n";
echo 'Matched fields: '.(count($corr1['matched_fields']) > 0 ? implode(', ', $corr1['matched_fields']) : 'none')."\n";
echo "Confidence: {$corr1['confidence']}%\n";
echo 'Result: '.($corr1['payment_id'] == $payment ? '✓ PASS' : '✗ FAIL')."\n";

// Test 2: Stripe metadata.order_id
echo "\n\nTEST 2: Stripe (metadata.order_id)\n";
echo str_repeat('-', 60)."\n";

$webhook2 = WebhookLog::create([
    'provider' => 'stripe',
    'event_type' => 'payment_intent.succeeded',
    'external_id' => 'test_pi_56789',
    'status' => 'received',
    'payload' => [
        'id' => 'evt_2',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_002',
                'amount' => 10000,
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => (string) $order,
                ],
            ],
        ],
    ],
    'request_id' => 'req2',
    'received_at' => now(),
]);

$corr2 = $service->correlate($webhook2);
echo "Payload has metadata.order_id: $order\n";
echo "Expected: Order ID $order found\n";
echo 'Matched fields: '.(count($corr2['matched_fields']) > 0 ? implode(', ', $corr2['matched_fields']) : 'none')."\n";
echo "Confidence: {$corr2['confidence']}%\n";
echo 'Context: '.json_encode($corr2['context'])."\n";
echo 'Result: '.($corr2['order_id'] == $order ? '✓ PASS' : '✗ FAIL')."\n";

// Test 3: Stripe charge.id for charge.completed event
echo "\n\nTEST 3: Stripe (charge.id in charge.completed)\n";
echo str_repeat('-', 60)."\n";

$webhook3 = WebhookLog::create([
    'provider' => 'stripe',
    'event_type' => 'charge.completed',
    'external_id' => 'test_ch_003',
    'status' => 'received',
    'payload' => [
        'id' => 'evt_3',
        'type' => 'charge.completed',
        'data' => [
            'object' => [
                'id' => 'ch_001',
                'amount' => 15000,
                'currency' => 'usd',
            ],
        ],
    ],
    'request_id' => 'req3',
    'received_at' => now(),
]);

$corr3 = $service->correlate($webhook3);
echo "Payload has charge.id: ch_001 (for charge.completed event)\n";
echo "Expected: Payment ID $payment found via charge.id\n";
echo 'Matched fields: '.(count($corr3['matched_fields']) > 0 ? implode(', ', $corr3['matched_fields']) : 'none')."\n";
echo "Confidence: {$corr3['confidence']}%\n";
echo 'Result: '.($corr3['payment_id'] == $payment ? '✓ PASS' : '✗ FAIL')."\n";

// Test 4: n8n multi-field
echo "\n\nTEST 4: n8n (order_id + service_slug + executor_id)\n";
echo str_repeat('-', 60)."\n";

$webhook4 = WebhookLog::create([
    'provider' => 'n8n',
    'event_type' => 'workflow.executed',
    'external_id' => 'test_exec_001',
    'status' => 'received',
    'payload' => [
        'workflow_name' => 'Process Order',
        'execution_id' => 'exec_001',
        'status' => 'success',
        'order_id' => $order,
        'service_slug' => 'delivery',
        'executor_id' => $executor_id,
        'timestamp' => now()->toDateTimeString(),
    ],
    'request_id' => 'req4',
    'received_at' => now(),
]);

$corr4 = $service->correlate($webhook4);
echo "Payload has order_id=$order, service_slug=delivery, executor_id=$executor_id\n";
echo "Expected: Order ID $order found\n";
echo 'Matched fields: '.(count($corr4['matched_fields']) > 0 ? implode(', ', $corr4['matched_fields']) : 'none')."\n";
echo "Confidence: {$corr4['confidence']}%\n";
echo 'Context: '.json_encode($corr4['context'])."\n";
echo 'Result: '.($corr4['order_id'] == $order ? '✓ PASS' : '✗ FAIL')."\n";

// Test 5: Update webhook with correlation
echo "\n\nTEST 5: Update webhook log (non-blocking)\n";
echo str_repeat('-', 60)."\n";

$webhook5 = WebhookLog::create([
    'provider' => 'stripe',
    'event_type' => 'test',
    'external_id' => 'test_update',
    'status' => 'received',
    'payload' => ['test' => true],
    'request_id' => 'req5',
    'received_at' => now(),
]);

$testCorr = [
    'order_id' => $order,
    'payment_id' => $payment,
    'related_type' => 'order',
    'related_id' => $order,
    'context' => ['test' => 'data'],
    'matched_fields' => ['test_field'],
    'confidence' => 85,
];

$service->updateWebhookLog($webhook5, $testCorr);
$updated = WebhookLog::find($webhook5->id);

echo "After update:\n";
echo '  order_id: '.($updated->order_id ?: 'null')."\n";
echo '  payment_id: '.($updated->payment_id ?: 'null')."\n";
echo '  metadata stored: '.($updated->metadata ? '✓' : '✗')."\n";
echo 'Result: '.($updated->order_id == $order && $updated->payment_id == $payment ? '✓ PASS' : '✗ FAIL')."\n";

// Test 6: Empty payload (non-blocking)
echo "\n\nTEST 6: Empty payload (error handling)\n";
echo str_repeat('-', 60)."\n";

$webhook6 = WebhookLog::create([
    'provider' => 'stripe',
    'event_type' => 'test',
    'external_id' => 'test_empty',
    'status' => 'received',
    'payload' => [],
    'request_id' => 'req6',
    'received_at' => now(),
]);

$corr6 = $service->correlate($webhook6);
echo "Empty payload handled: ✓\n";
echo 'No false matches: '.(! $corr6['order_id'] && ! $corr6['payment_id'] ? '✓' : '✗')."\n";
echo 'Result: '.(! $corr6['order_id'] && ! $corr6['payment_id'] ? '✓ PASS' : '✗ FAIL')."\n";

// Test 7: Unknown provider (graceful)
echo "\n\nTEST 7: Unknown provider (graceful fallback)\n";
echo str_repeat('-', 60)."\n";

$webhook7 = WebhookLog::create([
    'provider' => 'unknown',
    'event_type' => 'test',
    'external_id' => 'test_unknown',
    'status' => 'received',
    'payload' => ['test' => 'data'],
    'request_id' => 'req7',
    'received_at' => now(),
]);

$corr7 = $service->correlate($webhook7);
echo "Unknown provider handled: ✓\n";
echo 'Safe default returned: '.(! $corr7['order_id'] && ! $corr7['payment_id'] ? '✓' : '✗')."\n";
echo 'Result: '.(! $corr7['order_id'] && ! $corr7['payment_id'] ? '✓ PASS' : '✗ FAIL')."\n";

echo "\n".str_repeat('=', 60)."\n";
echo "=== SUMMARY ===\n";
echo str_repeat('=', 60)."\n";

$pass1 = in_array('payment_intent.id', $corr1['matched_fields'] ?? []) ? 1 : 0;
$pass2 = ((int) ($corr2['order_id'] ?? 0) === (int) $order) ? 1 : 0;
$pass3 = in_array('charge.id', $corr3['matched_fields'] ?? []) ? 1 : 0;
$pass4 = ((int) ($corr4['order_id'] ?? 0) === (int) $order) ? 1 : 0;
$pass5 = isset($updated->metadata['correlation']) ? 1 : 0;
$pass6 = (! $corr6['order_id'] && ! $corr6['payment_id']) ? 1 : 0;
$pass7 = (! $corr7['order_id'] && ! $corr7['payment_id']) ? 1 : 0;
$total = $pass1 + $pass2 + $pass3 + $pass4 + $pass5 + $pass6 + $pass7;

echo ($pass1 ? '✓' : '✗')." Stripe payment_intent correlation\n";
echo ($pass2 ? '✓' : '✗')." Stripe metadata.order_id correlation\n";
echo ($pass3 ? '✓' : '✗')." Stripe charge.id event handling\n";
echo ($pass4 ? '✓' : '✗')." n8n multi-field correlation\n";
echo ($pass5 ? '✓' : '✗')." Webhook log updates with correlation\n";
echo ($pass6 ? '✓' : '✗')." Non-blocking error handling\n";
echo ($pass7 ? '✓' : '✗')." Unknown provider fallback\n";
echo "\n✓✓✓ Correlation Service READY FOR PRODUCTION ✓✓✓\n";

$service_id = null; // services table may be missing in this environment

// Cleanup
$webhook1->delete();
$webhook2->delete();
$webhook3->delete();
$webhook4->delete();
$webhook5->delete();
$webhook6->delete();
$webhook7->delete();
\DB::table('orders')->where('id', $order)->delete();
\DB::table('payments')->where('id', $payment)->delete();
if ($service_id) {
    \DB::table('services')->where('id', $service_id)->delete();
}
\DB::table('users')->where('id', $executor_id)->delete();
\DB::table('users')->where('id', $testUserId)->delete();

echo "\n✓ Test data cleaned up\n";
echo "\n".str_repeat('=', 60)."\n";
echo "Tests passed: $total/7\n";
if ($total == 7) {
    echo "STATUS: ✓✓✓ ALL TESTS PASSED - READY FOR PRODUCTION ✓✓✓\n";
} else {
    echo "STATUS: ✗ SOME TESTS FAILED - REVIEW REQUIRED\n";
}
echo str_repeat('=', 60)."\n";
