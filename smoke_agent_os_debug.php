<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$run = App\Domain\AgentOS\Models\AgentRun::query()->latest('id')->first();
if (!$run) { echo "NO_RUN\n"; exit(0);} 
$run->load('artifacts');
echo 'RUN_ID=' . $run->id . PHP_EOL;
echo 'STATUS=' . $run->status . PHP_EOL;
echo 'METADATA=' . json_encode($run->metadata) . PHP_EOL;
$sum = 0;
foreach ($run->artifacts as $a) { $sum += (int) data_get($a->metadata, 'findings_count', 0);} 
echo 'ARTIFACT_FINDINGS_SUM=' . $sum . PHP_EOL;
