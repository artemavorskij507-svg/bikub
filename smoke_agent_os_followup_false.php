<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config([
    'agent-os.execution_mode' => 'sync',
    'agent-os.tool_fallback.enabled' => true,
    'agent-os.audit.auto_followup_on_findings' => false,
]);

$run = app(App\Domain\AgentOS\Actions\StartAgentRunAction::class)->execute([
    'organization_id' => '00000000-0000-0000-0000-000000009902',
    'tenant_id' => 1,
    'goal' => 'audit project',
    'risk_level' => 'medium',
    'idempotency_key' => 'smoke-followup-false-' . time(),
    'metadata' => ['source' => 'smoke'],
]);
$run = app(App\Domain\AgentOS\Services\RunOrchestratorService::class)->run($run);
$run = $run->fresh();

echo 'STATUS=' . $run->status . PHP_EOL;
echo 'FINDINGS=' . (int) data_get($run->metadata, 'audit_findings_count', 0) . PHP_EOL;
echo 'FOLLOWUP_CREATED=' . (data_get($run->metadata, 'followup_phase_created', false) ? '1' : '0') . PHP_EOL;
echo 'STEPS=' . $run->steps()->count() . PHP_EOL;
