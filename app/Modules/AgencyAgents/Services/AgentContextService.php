<?php

namespace App\Modules\AgencyAgents\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentContextService
{
    /**
     * Get a comprehensive summary of the database schema and current stats.
     */
    public function getProjectContext(): string
    {
        $context = "PROJECT CONTEXT (Bikube Database Schema & Stats)\n";
        $context .= "=============================================\n\n";

        $context .= "MAIN TABLES & STATS:\n";
        
        $tables = [
            'users' => 'System users/accounts',
            'orders' => 'General service orders',
            'classified_ads' => 'Marketplace advertisements',
            'service_jobs' => 'Active tasks and jobs',
            'service_types' => 'Defined services like cleaning, moving, etc.',
            'restaurants' => 'Food delivery partners',
            'agency_agents' => 'Your fellow AI agents',
        ];

        foreach ($tables as $table => $description) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $context .= "- {$table}: {$count} records ({$description})\n";
                
                // Add column info for key tables
                if (in_array($table, ['users', 'orders', 'classified_ads'])) {
                    $columns = Schema::getColumnListing($table);
                    $context .= "  Columns: " . implode(', ', array_slice($columns, 0, 15)) . (count($columns) > 15 ? '...' : '') . "\n";
                }
            }
        }

        $context .= "\nROUTING INFO:\n";
        $context .= "- API Endpoints available for data retrieval\n";
        $context .= "- Admin Panel segments: Logistics, Eco, Roadside, Social Care\n";

        return $context;
    }
}
