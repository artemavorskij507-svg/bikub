#!/usr/bin/env php
<?php

/**
 * Script to test webhook signature validation
 * Usage: php scripts/test_webhook_signatures.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:2244']);

// Test 1: Stripe with valid signature
echo "=== Test 1: Stripe with valid signature ===\n";
$stripeSecret = env('STRIPE_WEBHOOK_SECRET', 'test_secret_123');
$timestamp = time();
$payload = json_encode(['type' => 'payment_intent.succeeded', 'id' => 'pi_12345', 'amount' => 1000]);

$signedContent = "$timestamp.$payload";
$signature = hash_hmac('sha256', $signedContent, $stripeSecret);
$stripeSignatureHeader = "t=$timestamp,v1=$signature";

try {
    $response = $client->post('api/webhooks/stripe', [
        'headers' => [
            'Stripe-Signature' => $stripeSignatureHeader,
            'Content-Type' => 'application/json',
        ],
        'body' => $payload,
    ]);
    echo "Status: {$response->getStatusCode()}\n";
    echo 'Response: '.$response->getBody()."\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

// Test 2: Stripe with invalid signature
echo "\n=== Test 2: Stripe with invalid signature ===\n";
$badSignature = "t=$timestamp,v1=invalid_sig";

try {
    $response = $client->post('api/webhooks/stripe', [
        'headers' => [
            'Stripe-Signature' => $badSignature,
            'Content-Type' => 'application/json',
        ],
        'body' => $payload,
    ]);
    echo "Status: {$response->getStatusCode()}\n";
    echo 'Response: '.$response->getBody()."\n";
} catch (\Exception $e) {
    echo "Status: {$e->getResponse()->getStatusCode()}\n";
    echo 'Error: '.$e->getResponse()->getBody()."\n";
}

// Test 3: n8n with valid signature
echo "\n=== Test 3: n8n with valid signature ===\n";
$n8nSecret = env('N8N_WEBHOOK_SECRET', 'n8n_secret_456');
$n8nTimestamp = time();
$n8nPayload = json_encode(['type' => 'workflow.executed', 'execution_id' => 'exec_789']);

$n8nSignedContent = "$n8nTimestamp.$n8nPayload";
$n8nSignature = hash_hmac('sha256', $n8nSignedContent, $n8nSecret);

try {
    $response = $client->post('api/webhooks/n8n', [
        'headers' => [
            'X-N8N-Signature' => $n8nSignature,
            'X-N8N-Timestamp' => $n8nTimestamp,
            'Content-Type' => 'application/json',
        ],
        'body' => $n8nPayload,
    ]);
    echo "Status: {$response->getStatusCode()}\n";
    echo 'Response: '.$response->getBody()."\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

// Test 4: n8n with invalid signature
echo "\n=== Test 4: n8n with invalid signature ===\n";

try {
    $response = $client->post('api/webhooks/n8n', [
        'headers' => [
            'X-N8N-Signature' => 'invalid_n8n_sig',
            'X-N8N-Timestamp' => $n8nTimestamp,
            'Content-Type' => 'application/json',
        ],
        'body' => $n8nPayload,
    ]);
    echo "Status: {$response->getStatusCode()}\n";
    echo 'Response: '.$response->getBody()."\n";
} catch (\Exception $e) {
    echo "Status: {$e->getResponse()->getStatusCode()}\n";
    echo 'Error: '.$e->getResponse()->getBody()."\n";
}

// Test 5: Check webhook_logs in DB
echo "\n=== Test 5: Check webhook_logs in database ===\n";
$logs = \App\Models\WebhookLog::orderByDesc('id')->limit(10)->get();
foreach ($logs as $log) {
    echo "ID={$log->id} provider={$log->provider} status={$log->status} event={$log->event_type} error={$log->error_message}\n";
}

// Test 6: Check audit logs
echo "\n=== Test 6: Check audit logs for webhooks ===\n";
$auditLogs = \App\Models\AuditLog::where('action', 'like', 'webhook%')->orderByDesc('id')->limit(10)->get();
foreach ($auditLogs as $log) {
    echo "action={$log->action} model_id={$log->model_id} error_msg={$log->before}\n";
}

echo "\n✓ Test script completed.\n";
