<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::where('email', 'keks@glf.no')->first();
if (! $u) { echo "NO_USER\n"; exit(0);} 

echo "USER_ID={$u->id}\n";
echo "ROLES=" . implode(',', $u->roles()->pluck('name')->all()) . "\n";
echo "PERMS=" . implode(',', $u->getAllPermissions()->pluck('name')->all()) . "\n";
