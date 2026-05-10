<?php

namespace App\Modules\AgencyAgents\Console;

use App\Modules\AgencyAgents\Models\OfficeZone;
use App\Modules\AgencyAgents\Services\AgentInitializationService;
use Illuminate\Console\Command;

class InitializeAgentsCommand extends Command
{
    protected $signature = 'agency:initialize
                            {--force : Force reinitialize all agents}
                            {--category= : Initialize only agents from specific category}
                            {--zones : Initialize office zones only}
                            {--logistics-operations : Launch the logistics multi-agent mode with 20+ assigned agents}';

    protected $description = 'Initialize AI agents from agency-agents repository for 2D office';

    public function handle(AgentInitializationService $service): int
    {
        $this->info('Initializing Agency Agents 2D Office System...');
        $this->newLine();

        if ($this->option('zones') || !$this->option('category')) {
            $this->info('Initializing office zones...');
            OfficeZone::initializeDefaultZones();
            $this->info('Initialized ' . OfficeZone::count() . ' office zones');
            $this->newLine();
        }

        if ($this->option('zones')) {
            return Command::SUCCESS;
        }

        if ($this->option('force')) {
            $this->warn('Force mode enabled - existing agents will be updated');
        }

        $category = $this->option('category');

        if ($this->option('logistics-operations')) {
            $this->info('Launching logistics operations mode...');
            $result = $service->initializeLogisticsOperationsAgents();
        } elseif ($category) {
            $this->info("Initializing agents from category: {$category}");
            $result = $this->initializeCategory($service, $category);
        } else {
            $this->info('Initializing all agents...');
            $result = $service->initializeAllAgents();
        }

        $this->displayResults($result);

        return Command::SUCCESS;
    }

    private function initializeCategory(AgentInitializationService $service, string $category): array
    {
        $basePath = base_path("agency-agents/{$category}");

        if (!\File::exists($basePath)) {
            $this->error("Category directory not found: {$category}");
            return ['initialized' => [], 'errors' => [['error' => "Category not found: {$category}"]], 'total' => 0];
        }

        $initialized = [];
        $errors = [];
        foreach (\File::glob("{$basePath}/*.md") as $file) {
            try {
                $agent = $service->initializeAgentFromFile($file, $category);
                if ($agent) {
                    $initialized[] = $agent;
                }
            } catch (\Throwable $e) {
                $errors[] = ['file' => $file, 'error' => $e->getMessage()];
            }
        }

        return ['initialized' => $initialized, 'errors' => $errors, 'total' => count($initialized)];
    }

    private function displayResults(array $result): void
    {
        $this->newLine();
        $this->info('Initialization Results:');
        $this->newLine();

        if (!empty($result['initialized'])) {
            $tableData = [];
            foreach ($result['initialized'] as $agent) {
                $tableData[] = [$agent->emoji, $agent->name, $agent->category, $agent->current_zone, $agent->status, "({$agent->position_x}, {$agent->position_y})"];
            }
            $this->table(['Emoji', 'Name', 'Category', 'Zone', 'Status', 'Position'], $tableData);
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->error("- {$error['file']}: {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('Initialization complete!');
        $this->info('Total agents in system: ' . \App\Modules\AgencyAgents\Models\Agent::count());
        $this->info('Total office zones: ' . OfficeZone::count());
        if (isset($result['operations_total'])) {
            $this->info('Logistics module assignments created: ' . $result['operations_total']);
        }
    }
}

