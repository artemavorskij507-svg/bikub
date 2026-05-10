<?php

namespace App\Modules\AgencyAgents;

use App\Modules\AgencyAgents\Services\AgentCommunicationService;
use App\Modules\AgencyAgents\Services\AgentEventBusService;
use App\Modules\AgencyAgents\Services\AgentInitializationService;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;
use Illuminate\Support\ServiceProvider;

class AgencyAgentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentInitializationService::class);
        $this->app->singleton(AgentCommunicationService::class);
        $this->app->singleton(AgentEventBusService::class);
        $this->app->singleton(AgentMonitoringService::class);

        $this->mergeConfigFrom(base_path('config/agency-agents.php'), 'agency-agents');
    }

    public function boot(): void
    {
        $routesPath = base_path('routes/agency-agents.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\AgencyAgents\Console\InitializeAgentsCommand::class,
                \App\Modules\AgencyAgents\Console\MonitorAgentsCommand::class,
            ]);
        }
    }
}

