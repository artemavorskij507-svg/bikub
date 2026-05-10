<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdminIpRule;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Ensure a Request is available with IP and UA
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '203.0.113.9';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cli-test-agent/1.0';
$request = Request::create('/', 'POST', [], [], [], $_SERVER);
app()->instance('request', $request);

// Attempt to find an admin user
// Try to find a user with role 'admin' (Spatie roles)
$admin = User::whereHas('roles', function ($q) {
    $q->where('name', 'admin');
})->first();
if (! $admin) {
    // fallback to first user
    $admin = User::first();
}
if ($admin) {
    Auth::login($admin);
    echo "Logged in as user id: {$admin->id}\n";
} else {
    echo "No user found to authenticate; actions will be anonymous.\n";
}

// Report current active allow count
$activeAllowCount = AdminIpRule::where('type', 'allow')->where('is_active', true)->count();
echo "Existing active allow rules: {$activeAllowCount}\n";

// Create a deny-rule to delete later
$deny = AdminIpRule::create([
    'type' => 'deny',
    'ip_range' => '203.0.113.200',
    'description' => 'test deny rule',
    'is_active' => true,
]);

echo "Created deny rule id={$deny->id}\n";

// Create an allow-rule
// To test the "last active allow" edge-case we temporarily deactivate other active allow rules (if any),
// create a new allow rule, attempt to deactivate it (should be blocked), then restore previous state.
$otherActive = AdminIpRule::where('type', 'allow')->where('is_active', true)->pluck('id')->toArray();
echo 'Temporarily deactivating other active allow rules: '.json_encode($otherActive)."\n";

// Backup current states
$backup = [];
foreach ($otherActive as $id) {
    $r = AdminIpRule::find($id);
    if ($r) {
        $backup[$id] = $r->is_active;
    }
}

// Deactivate other active allow rules using direct query to avoid observer side-effects
if (! empty($otherActive)) {
    \Illuminate\Support\Facades\DB::table('admin_ip_rules')->whereIn('id', $otherActive)->update(['is_active' => false]);
}

try {
    $allow = AdminIpRule::create([
        'type' => 'allow',
        'ip_range' => '203.0.113.5',
        'description' => 'test allow rule',
        'is_active' => true,
    ]);

    echo "Created allow rule id={$allow->id}\n";

    // Try to deactivate the allow rule
    try {
        $allow->is_active = false;
        $allow->save();
        echo "Deactivated allow rule id={$allow->id} (SUCCESS)\n";
    } catch (\Throwable $e) {
        echo 'Deactivation blocked: '.$e->getMessage()."\n";
    }
} finally {
    // Restore backups
    foreach ($backup as $id => $val) {
        \Illuminate\Support\Facades\DB::table('admin_ip_rules')->where('id', $id)->update(['is_active' => $val ? true : false]);
    }
    echo "Restored previous allow-rule states.\n";
}

// Try to delete deny rule
try {
    $denyId = $deny->id;
    $deny->delete();
    echo "Deleted deny rule id={$denyId}\n";
} catch (\Throwable $e) {
    echo 'Delete failed: '.$e->getMessage()."\n";
}

// Query recent audit logs for AdminIpRule
$logs = AuditLog::where('model_type', 'like', '%AdminIpRule%')->orderByDesc('id')->limit(20)->get();

echo "\nRecent Audit Logs for AdminIpRule:\n";
foreach ($logs as $l) {
    echo "id={$l->id} action={$l->action} model_type={$l->model_type} model_id={$l->model_id} actor_user_id={$l->actor_user_id} ip={$l->ip_address} created_at={$l->created_at}\n";
    if ($l->before) {
        echo '  before: '.json_encode($l->before)."\n";
    }
    if ($l->after) {
        echo '  after: '.json_encode($l->after)."\n";
    }
}

echo "\nScript finished.\n";
