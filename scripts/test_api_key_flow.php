<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Setup request context
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '203.0.113.50';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'test-script/1.0';
$request = Request::create('/', 'POST', [], [], [], $_SERVER);
app()->instance('request', $request);

// Authenticate as admin user
$admin = User::whereHas('roles', function ($q) {
    $q->where('name', 'admin');
})->first();
if (! $admin) {
    $admin = User::first();
}

if ($admin) {
    Auth::login($admin);
    echo "✓ Logged in as user id: {$admin->id}\n";
} else {
    echo "✗ No user found\n";
    exit(1);
}

$service = app(ApiKeyService::class);

// 1. CREATE API KEY
echo "\n=== 1. CREATE API KEY ===\n";
$result = $service->generate(
    'user',
    $admin->id,
    'Test Key '.time(),
    ['read', 'write'],
    30,
    false
);

echo "Plaintext key: {$result['api_key']}\n";
echo "Key ID: {$result['id']}\n";

$key = ApiKey::find($result['id']);
echo 'Key hash stored: '.substr($key->key_hash, 0, 10)."...\n";
echo 'Status: '.$service->getStatus($key)."\n";

// 2. VALIDATE KEY
echo "\n=== 2. VALIDATE KEY ===\n";
$validated = $service->validateKey($result['api_key']);
if ($validated && $validated->id === $key->id) {
    echo "✓ Key validation successful\n";
} else {
    echo "✗ Key validation failed\n";
}

// 3. ROTATE KEY
echo "\n=== 3. ROTATE KEY ===\n";
$rotateResult = $service->rotate($key, ['read', 'write', 'delete']);
echo "New plaintext key: {$rotateResult['api_key']}\n";
echo "New key ID: {$rotateResult['id']}\n";

$oldKey = ApiKey::find($key->id);
$newKey = ApiKey::find($rotateResult['id']);
echo 'Old key status: '.$service->getStatus($oldKey).' (revoked_at: '.($oldKey->revoked_at?->toDateTimeString() ?? 'null').")\n";
echo 'New key status: '.$service->getStatus($newKey)."\n";

// 4. REVOKE KEY
echo "\n=== 4. REVOKE KEY ===\n";
$service->revoke($newKey);
$newKey->refresh();
echo 'Key revoked_at: '.($newKey->revoked_at?->toDateTimeString() ?? 'null')."\n";
echo 'Status: '.$service->getStatus($newKey)."\n";

// 5. CHECK AUDIT LOGS
echo "\n=== 5. AUDIT LOGS ===\n";
$logs = AuditLog::where('model_type', 'like', '%ApiKey%')->orderByDesc('id')->limit(20)->get();

echo "Recent API Key audit events:\n";
foreach ($logs as $l) {
    echo "  id={$l->id} action={$l->action} model_id={$l->model_id} actor_user_id={$l->actor_user_id} ip={$l->ip_address} at={$l->created_at}\n";
    if ($l->before) {
        echo '    before: '.json_encode($l->before, JSON_UNESCAPED_SLASHES)."\n";
    }
    if ($l->after) {
        echo '    after: '.json_encode($l->after, JSON_UNESCAPED_SLASHES)."\n";
    }
}

echo "\n✓ Test script completed.\n";
