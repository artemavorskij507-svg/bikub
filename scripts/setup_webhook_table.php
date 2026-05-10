#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

echo "=== Recreating webhook_logs table ===\n\n";

// Drop if exists
if (Schema::hasTable('webhook_logs')) {
    Schema::drop('webhook_logs');
    echo "✓ Dropped webhook_logs table\n";
}

// Create new table with full structure
Schema::create('webhook_logs', function (Blueprint $table) {
    $table->id();

    // Webhook metadata
    $table->string('provider')->nullable()->index();
    $table->string('event_type')->nullable()->index();
    $table->string('external_id')->nullable()->index();

    // Status and response
    $table->string('status')->default('received')->index();
    $table->integer('http_status')->nullable();

    // Data
    $table->json('payload')->nullable();
    $table->text('error_message')->nullable();

    // Tracking
    $table->string('request_id')->nullable()->index();
    $table->timestamp('received_at')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->integer('attempt')->default(0);

    // Business links
    $table->unsignedBigInteger('order_id')->nullable()->index();
    $table->unsignedBigInteger('payment_id')->nullable()->index();

    $table->timestamps();
});

echo "✓ Created webhook_logs table\n\n";

// Test insert
echo "Testing insert...\n";
try {
    $id = \Illuminate\Support\Facades\DB::table('webhook_logs')->insertGetId([
        'provider' => 'test',
        'event_type' => 'test.event',
        'external_id' => 'test_123',
        'status' => 'received',
        'http_status' => null,
        'payload' => json_encode(['test' => true]),
        'error_message' => null,
        'request_id' => 'test_req_001',
        'received_at' => now(),
        'attempt' => 0,
    ]);
    echo "✓ Insert successful, ID: $id\n";

    // Clean up
    \Illuminate\Support\Facades\DB::table('webhook_logs')->where('id', $id)->delete();
    echo "✓ Cleanup successful\n";
} catch (\Throwable $e) {
    echo '✗ Insert failed: '.$e->getMessage()."\n";
}

echo "\n✓ webhook_logs table is ready for webhooks!\n";
