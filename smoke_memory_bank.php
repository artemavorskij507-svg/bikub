<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config([
    'agent-os.execution_mode' => 'sync',
    'agent-os.chat.async_enabled' => false,
]);

$start = app(App\Domain\AgentOS\Actions\StartAgentRunAction::class);
$mem = app(App\Domain\AgentOS\Services\AgentMemoryBankService::class);
$orc = app(App\Domain\AgentOS\Services\RunOrchestratorService::class);

$run = $start->execute([
    'organization_id' => '00000000-0000-0000-0000-000000009903',
    'tenant_id' => 1,
    'goal' => 'memory test audit',
    'risk_level' => 'medium',
    'idempotency_key' => 'memory-test-' . time(),
    'metadata' => ['source' => 'smoke_memory'],
]);
$mem->rememberChatMessage($run, 'coordinator', 'user', 'Нужен аудит и исправления', null, ['source' => 'smoke']);
$run = $orc->run($run)->fresh();
$mem->rememberChatMessage($run, 'coordinator', 'assistant', 'Отчет сформирован', null, ['source' => 'smoke']);

$count = App\Domain\AgentOS\Models\AgentMemory::query()->where('run_id', $run->id)->count();
$workerCount = App\Domain\AgentOS\Models\AgentMemory::query()->where('run_id', $run->id)->where('memory_type', 'step_summary')->count();

echo 'RUN=' . $run->id . PHP_EOL;
echo 'STATUS=' . $run->status . PHP_EOL;
echo 'MEMORIES=' . $count . PHP_EOL;
echo 'WORKER_STEP_MEMORIES=' . $workerCount . PHP_EOL;
