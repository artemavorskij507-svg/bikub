#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== webhook_logs Table Structure ===\n\n";

// Get columns
$columns = DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'webhook_logs' ORDER BY ordinal_position");

foreach ($columns as $col) {
    $nullable = $col->is_nullable === 'YES' ? 'nullable' : 'NOT NULL';
    echo sprintf("%-20s %-20s %s\n", $col->column_name, $col->data_type, $nullable);
}

echo "\n=== Check if table is empty ===\n";
$count = DB::table('webhook_logs')->count();
echo "Total records: $count\n";

echo "\n=== Sample insert test ===\n";
try {
    $id = DB::table('webhook_logs')->insertGetId([
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
    DB::table('webhook_logs')->where('id', $id)->delete();
    echo "✓ Cleanup successful\n";
} catch (\Throwable $e) {
    echo '✗ Insert failed: '.$e->getMessage()."\n";
}
