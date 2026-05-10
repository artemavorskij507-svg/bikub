<?php

namespace App\Console\Commands;

use App\Domain\Routing\Actions\BuildRoutingBaselineReportAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OpsRoutingShadowReportCommand extends Command
{
    protected $signature = 'ops:routing-shadow-report
        {--organization= : Optional organization scope}
        {--days=3 : Lookback window (1-30 days)}
        {--json= : Optional report path}';

    protected $description = 'Build routing shadow baseline report for observability and quality gating';

    public function handle(BuildRoutingBaselineReportAction $buildRoutingBaselineReportAction): int
    {
        $organizationId = (string) ($this->option('organization') ?: '');
        $days = max(1, min(30, (int) ($this->option('days') ?: 3)));
        $report = $buildRoutingBaselineReportAction->execute(
            $organizationId !== '' ? $organizationId : null,
            $days
        );

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-routing-shadow-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $metrics = (array) ($report['metrics'] ?? []);
        $health = (array) ($report['provider_health'] ?? []);

        $this->info('Routing shadow baseline report generated.');
        $this->line('Provider: '.(string) ($health['provider'] ?? 'unknown'));
        $this->line('Reachable: '.((bool) ($health['reachable'] ?? false) ? 'yes' : 'no'));
        $this->line('Latency (ms): '.(($health['latency_ms'] ?? null) === null ? 'n/a' : (string) $health['latency_ms']));
        $this->line('Total snapshots: '.(string) ($metrics['total_snapshots'] ?? 0));
        $this->line('Avg eta delta (s): '.(string) ($metrics['avg_eta_delta_seconds'] ?? 0));
        $this->line('Avg delta (%): '.(string) ($metrics['avg_delta_percent'] ?? 0));
        $this->line('Ranking drift count: '.(string) ($metrics['ranking_drift_count'] ?? 0));
        $this->line('Provider errors count: '.(string) ($metrics['provider_errors_count'] ?? 0));
        $this->line('Report: '.$jsonPath);

        return self::SUCCESS;
    }
}

