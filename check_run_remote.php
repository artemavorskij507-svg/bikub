<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$run = App\Domain\AgentOS\Models\AgentRun::find((int)($argv[1] ?? 0));
if (!$run) { echo "run_not_found\n"; exit(1); }
echo 'run_status='.$run->status.' terminal_reason='.($run->terminal_reason ?? '').PHP_EOL;
foreach ($run->steps()->orderBy('id')->get(['id','step_type','status','metadata']) as $s) {
    echo $s->id.' '.$s->step_type.' '.$s->status;
    $note = (string) data_get($s->metadata, 'system_note', '');
    if ($note !== '') {
        echo ' system_note='.$note;
    }
    echo PHP_EOL;
}
