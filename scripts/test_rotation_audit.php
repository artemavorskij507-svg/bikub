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
$_SERVER['REMOTE_ADDR'] = '203.0.113.99';
$_SERVER['HTTP_USER_AGENT'] = 'test-rotation-check/1.0';
$request = Request::create('/', 'POST', [], [], [], $_SERVER);
app()->instance('request', $request);

// Authenticate
$admin = User::first();
Auth::login($admin);
echo "Logged in as: {$admin->id}\n\n";

$service = app(ApiKeyService::class);

// Create key
$result = $service->generate('user', $admin->id, 'rotation-test-'.time(), ['read'], 30, false);
$key = ApiKey::find($result['id']);
echo "Created key: {$result['id']}\n";

// Get logs BEFORE rotation
$logsBefore = AuditLog::where('model_type', 'like', '%ApiKey%')->where('model_id', $key->id)->count();
echo "Logs for this key before rotation: $logsBefore\n";

// Rotate via service (directly, not via Resource action)
$rotateResult = $service->rotate($key);
echo "Rotated. Old key ID: {$key->id}, New key ID: {$rotateResult['id']}\n";

// Check logs for old key
$oldLogs = AuditLog::where('model_type', 'like', '%ApiKey%')->where('model_id', $key->id)->orderByDesc('id')->get();
echo "\nLogs for OLD key after rotation:\n";
foreach ($oldLogs as $l) {
    echo "  action={$l->action} at={$l->created_at}\n";
}

// Check logs for new key
$newLogs = AuditLog::where('model_type', 'like', '%ApiKey%')->where('model_id', $rotateResult['id'])->orderByDesc('id')->get();
echo "\nLogs for NEW key after rotation:\n";
foreach ($newLogs as $l) {
    echo "  action={$l->action} at={$l->created_at}\n";
}

// Query for api_key_rotated specifically
$rotatedLogs = AuditLog::where('action', 'api_key_rotated')->where('model_id', $key->id)->get();
echo "\nExplicit api_key_rotated events for old key: ".$rotatedLogs->count()."\n";
if ($rotatedLogs->count() > 0) {
    foreach ($rotatedLogs as $l) {
        echo '  Found: '.json_encode(['before' => $l->before, 'after' => $l->after])."\n";
    }
}

echo "\n✓ Test complete.\n";
