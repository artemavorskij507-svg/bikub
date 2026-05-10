<?php

namespace App\Console\Commands;

use App\Domain\AgentOS\Actions\StartAgentRunAction;
use App\Domain\AgentOS\Jobs\ProcessAgentRunJob;
use App\Domain\AgentOS\Models\AgentRun;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class AgentOsSmokeChatCommand extends Command
{
    protected $signature = 'agent-os:smoke-chat
        {--goal=Smoke chat check : Goal text for the test run}
        {--organization-id= : Override organization_id}
        {--tenant-id= : Override tenant_id}
        {--user-id=1 : Actor id used for run creation/dispatch}
        {--timeout=90 : Seconds to wait for terminal status}';

    protected $description = 'Smoke-check Agent OS chat chain: create run -> dispatch -> terminal.';

    public function handle(StartAgentRunAction $startAgentRunAction): int
    {
        $goal = trim((string) $this->option('goal'));
        $userId = (int) $this->option('user-id');
        $timeout = max(10, (int) $this->option('timeout'));

        $organizationId = $this->option('organization-id');
        $tenantIdRaw = $this->option('tenant-id');
        $tenantId = is_numeric($tenantIdRaw) ? (int) $tenantIdRaw : null;

        if ($organizationId === null) {
            $organizationId = (string) data_get(AgentRun::query()->latest('id')->first(), 'organization_id');
        }

        if (! is_string($organizationId) || trim($organizationId) === '') {
            $organizationId = (string) Str::uuid();
        }

        $payload = [
            'organization_id' => (string) $organizationId,
            'tenant_id' => $tenantId,
            'goal' => $goal !== '' ? $goal : 'Smoke chat check',
            'risk_level' => 'medium',
            'requires_approval' => false,
            'deployment_allowed' => false,
            'idempotency_key' => sha1('smoke-chat|'.$organizationId.'|'.$tenantId.'|'.now()->format('YmdHis')),
            'created_by' => $userId,
            'updated_by' => $userId,
            'metadata' => [
                'source' => 'agent-os:smoke-chat',
            ],
        ];

        $run = $startAgentRunAction->execute($payload);

        $connection = (string) config('agent-os.chat.connection', 'redis');
        $queue = 'default';

        ProcessAgentRunJob::dispatch($run->id, $userId)
            ->onConnection($connection)
            ->onQueue($queue);

        $this->info(sprintf('Run %d created and dispatched on %s/%s', $run->id, $connection, $queue));

        $started = time();
        $terminalStatuses = ['completed', 'blocked', 'failed', 'ready_for_review', 'audit_completed', 'followup_required'];

        do {
            usleep(500000);
            $run = AgentRun::query()->find($run->id);

            if (! $run) {
                $this->error('Run record was deleted during smoke check.');
                return self::FAILURE;
            }

            $this->line(sprintf('status=%s progress=%d/%d',
                (string) $run->status,
                (int) $run->steps()->where('status', 'completed')->count(),
                (int) $run->steps()->count()
            ));

            if (in_array((string) $run->status, $terminalStatuses, true)) {
                $this->info(sprintf('Terminal reached: %s (reason: %s)', (string) $run->status, (string) ($run->terminal_reason ?? 'n/a')));
                return self::SUCCESS;
            }
        } while ((time() - $started) < $timeout);

        $this->error(sprintf('Timeout after %d seconds, last status: %s', $timeout, (string) $run->status));

        return self::FAILURE;
    }
}
