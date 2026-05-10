<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$roles = DB::table('roles')->select('id','name')->orderBy('id')->get();
$userRoles = DB::table('user_roles')->select('user_id','role_id')->orderBy('user_id')->get();
$users = App\Models\User::query()->select('id','email')->orderBy('id')->limit(20)->get();

echo "ROLES\n";
foreach ($roles as $r) { echo "{$r->id}:{$r->name}\n"; }

echo "USER_ROLES\n";
foreach ($userRoles as $ur) { echo "u{$ur->user_id}->r{$ur->role_id}\n"; }

echo "USERS\n";
foreach ($users as $u) { echo "{$u->id} {$u->email}\n"; }
