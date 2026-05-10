<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::where('email','keks@glf.no')->first();
if(!$u){echo "NO_USER\n";exit;}
$attrs=['id'=>$u->id,'email'=>$u->email,'organization_id'=>$u->organization_id??null,'tenant_id'=>$u->tenant_id??null];
echo json_encode($attrs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
