<?php

namespace App\Modules\AgencyAgents\Console;

use Illuminate\Console\Command;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;
use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\OfficeZone;
use App\Modules\AgencyAgents\Models\AgentActivity;

class MonitorAgentsCommand extends Command
{
    protected $signature = 'agency:monitor 
                            {--agent= : Monitor specific agent by ID}
                            {--report : Generate system report}
                            {--health : Check system health}
                            {--top : Show top performers}
                            {--zones : Show zone statistics}
                            {--activities : Show recent activities}';

    protected $description = 'Monitor AI agents performance and system health in 2D office';

    public function handle(AgentMonitoringService $service): int
    {
        if ($this->option('report')) {
            return $this->generateReport($service);
        }

        if ($this->option('health')) {
            return $this->checkHealth($service);
        }

        if ($this->option('top')) {
            return $this->showTopPerformers($service);
        }

        if ($this->option('zones')) {
            return $this->showZoneStats();
        }

        if ($this->option('activities')) {
            return $this->showRecentActivities();
        }

        $agentId = $this->option('agent');

        if ($agentId) {
            return $this->monitorAgent($service, $agentId);
        }

        return $this->showOverview($service);
    }

    private function showOverview(AgentMonitoringService $service): int
    {
        $this->info('📊 Agency Agents 2D Office Overview');
        $this->newLine();

        $overview = $service->getSystemOverview();

        // Agents status
        $this->info('🤖 Agents:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total', $overview['agents']['total']],
                ['Active', $overview['agents']['active']],
                ['Busy', $overview['agents']['busy']],
                ['Idle', $overview['agents']['idle']],
                ['Offline', $overview['agents']['offline']],
            ]
        );

        $this->newLine();

        // Tasks status
        $this->info('📋 Tasks:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total', $overview['tasks']['total']],
                ['Completed', $overview['tasks']['completed']],
                ['In Progress', $overview['tasks']['in_progress']],
                ['Pending', $overview['tasks']['pending']],
                ['Failed', $overview['tasks']['failed']],
            ]
        );

        $this->newLine();

        // Performance
        $this->info('📈 Performance:');
        $this->line("  Average Score: {$overview['performance']['average_score']}");
        $this->line("  Completion Rate: {$overview['performance']['completion_rate']}%");

        $this->newLine();

        // Communications
        $this->info('💬 Communications:');
        $this->line("  Total Messages: {$overview['communications']['total_messages']}");
        $this->line("  Unread Messages: {$overview['communications']['unread_messages']}");

        $this->newLine();

        // Zone occupancy
        $this->info('🏢 Zone Occupancy:');
        $zones = OfficeZone::all();
        foreach ($zones as $zone) {
            $percentage = round($zone->getOccupancyPercentage());
            $this->line("  {$zone->icon} {$zone->display_name}: {$zone->current_occupancy}/{$zone->capacity} ({$percentage}%)");
        }

        return Command::SUCCESS;
    }

    private function monitorAgent(AgentMonitoringService $service, int $agentId): int
    {
        $agent = Agent::find($agentId);

        if (!$agent) {
            $this->error("Agent with ID {$agentId} not found");
            return Command::FAILURE;
        }

        $this->info("🔍 Monitoring Agent: {$agent->name}");
        $this->newLine();

        $performance = $service->getAgentPerformance($agent);

        // Agent info
        $this->info('ℹ️  Agent Information:');
        $this->line("  Name: {$performance['agent']['name']}");
        $this->line("  Category: {$performance['agent']['category']}");
        $this->line("  Status: {$performance['agent']['status']}");
        $this->line("  Zone: {$agent->current_zone}");
        $this->line("  Position: ({$agent->position_x}, {$agent->position_y})");
        $this->line("  Activity: {$agent->current_activity}");

        $this->newLine();

        // Tasks
        $this->info('📋 Tasks:');
        $this->line("  Total: {$performance['tasks']['total']}");
        $this->line("  Completed: {$performance['tasks']['completed']}");
        $this->line("  Failed: {$performance['tasks']['failed']}");
        $this->line("  Success Rate: {$performance['tasks']['success_rate']}%");

        $this->newLine();

        // Performance
        $this->info('📈 Performance:');
        $this->line("  Current Score: {$performance['performance']['current_score']}");
        $this->line("  Tasks Completed: {$performance['performance']['tasks_completed']}");
        if ($performance['performance']['avg_completion_time']) {
            $this->line("  Avg Completion Time: {$performance['performance']['avg_completion_time']}s");
        }

        $this->newLine();

        // Communications
        $this->info('💬 Communications:');
        $this->line("  Sent: {$performance['communications']['sent']}");
        $this->line("  Received: {$performance['communications']['received']}");

        $this->newLine();

        // Recent activity
        if (!empty($performance['recent_activity']['tasks'])) {
            $this->info('🕐 Recent Tasks:');
            foreach ($performance['recent_activity']['tasks'] as $task) {
                $this->line("  • [{$task['status']}] {$task['title']}");
            }
        }

        return Command::SUCCESS;
    }

    private function generateReport(AgentMonitoringService $service): int
    {
        $this->info('📄 Generating System Report...');
        $this->newLine();

        $report = $service->generateReport('daily');

        $this->info('📊 Daily Report Summary:');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Tasks Created', $report['summary']['tasks_created']],
                ['Tasks Completed', $report['summary']['tasks_completed']],
                ['Messages Sent', $report['summary']['messages_sent']],
                ['Metrics Recorded', $report['summary']['metrics_recorded']],
            ]
        );

        $this->newLine();

        if (!empty($report['top_performers'])) {
            $this->info('🏆 Top Performers:');
            $this->table(
                ['Name', 'Category', 'Score'],
                collect($report['top_performers'])->map(function ($performer) {
                    return [$performer['name'], $performer['category'], $performer['score']];
                })->toArray()
            );
        }

        return Command::SUCCESS;
    }

    private function checkHealth(AgentMonitoringService $service): int
    {
        $this->info('🏥 Checking System Health...');
        $this->newLine();

        $health = $service->getSystemHealth();

        if ($health['status'] === 'healthy') {
            $this->info('✅ System is healthy!');
        } else {
            $this->warn('⚠️  Issues detected:');
            $this->newLine();

            foreach ($health['issues'] as $issue) {
                $icon = $issue['type'] === 'error' ? '❌' : '⚠️';
                $this->line("  {$icon} {$issue['message']}");
            }
        }

        $this->newLine();
        $this->line("Checked at: {$health['checked_at']}");

        return Command::SUCCESS;
    }

    private function showTopPerformers(AgentMonitoringService $service): int
    {
        $this->info('🏆 Top Performing Agents');
        $this->newLine();

        $topPerformers = $service->getTopPerformers(10);

        $this->table(
            ['#', 'Name', 'Category', 'Score', 'Tasks Completed', 'Zone'],
            $topPerformers->map(function ($agent, $index) {
                return [
                    $index + 1,
                    $agent->name,
                    $agent->category,
                    $agent->performance_score,
                    $agent->tasks_completed,
                    $agent->current_zone,
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    private function showZoneStats(): int
    {
        $this->info('🏢 Office Zone Statistics');
        $this->newLine();

        $zones = OfficeZone::all();

        $this->table(
            ['Zone', 'Icon', 'Occupancy', 'Capacity', 'Percentage'],
            $zones->map(function ($zone) {
                return [
                    $zone->display_name,
                    $zone->icon,
                    $zone->current_occupancy,
                    $zone->capacity,
                    round($zone->getOccupancyPercentage()) . '%',
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    private function showRecentActivities(): int
    {
        $this->info('🕐 Recent Agent Activities');
        $this->newLine();

        $activities = AgentActivity::with('agent')
            ->latest()
            ->limit(20)
            ->get();

        $this->table(
            ['Time', 'Agent', 'Activity', 'Zone', 'Description'],
            $activities->map(function ($activity) {
                return [
                    $activity->started_at->format('H:i:s'),
                    $activity->agent->emoji . ' ' . $activity->agent->name,
                    $activity->activity_type,
                    $activity->zone,
                    \Str::limit($activity->description, 40),
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }
}
