<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL only - indexes for performance

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumns('tasks', ['status', 'zone_id', 'sla_deadline_at']) && ! $this->hasIndex('tasks', 'tasks_status_zone_sla_idx')) {
                    $table->index(['status', 'zone_id', 'sla_deadline_at'], 'tasks_status_zone_sla_idx');
                }

                if (! $this->hasIndex('tasks', 'tasks_slot_status_idx')) {
                    $table->index(['slot_id', 'status'], 'tasks_slot_status_idx');
                }

                if (! $this->hasIndex('tasks', 'tasks_assignee_status_idx')) {
                    $table->index(['assignee_id', 'status'], 'tasks_assignee_status_idx');
                }

                if (Schema::hasColumn('tasks', 'batch_id') && ! $this->hasIndex('tasks', 'tasks_batch_id_idx')) {
                    $table->index('batch_id', 'tasks_batch_id_idx');
                }
            });
        }

        if (Schema::hasTable('schedule_slots')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                if (Schema::hasColumns('schedule_slots', ['zone_id', 'is_active', 'is_closed']) && ! $this->hasIndex('schedule_slots', 'schedule_slots_zone_active_idx')) {
                    $table->index(['zone_id', 'is_active', 'is_closed'], 'schedule_slots_zone_active_idx');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! $this->hasIndex('orders', 'orders_status_payment_idx')) {
                    $table->index(['status', 'payment_status'], 'orders_status_payment_idx');
                }

                if (Schema::hasColumn('orders', 'scheduled_at') && ! $this->hasIndex('orders', 'orders_scheduled_at_idx')) {
                    $table->index('scheduled_at', 'orders_scheduled_at_idx');
                }
            });
        }

        if (Schema::hasTable('traffic_incidents')) {
            Schema::table('traffic_incidents', function (Blueprint $table) {
                if (! $this->hasIndex('traffic_incidents', 'traffic_incidents_active_severity_idx')) {
                    $table->index(['status', 'severity', 'starts_at'], 'traffic_incidents_active_severity_idx');
                }

                if (! $this->hasIndex('traffic_incidents', 'traffic_incidents_location_idx')) {
                    $table->index(['lat', 'lng'], 'traffic_incidents_location_idx');
                }
            });
        }

        if (Schema::hasTable('travel_times')) {
            Schema::table('travel_times', function (Blueprint $table) {
                if (Schema::hasColumn('travel_times', 'measured_at') && ! $this->hasIndex('travel_times', 'travel_times_measured_at_idx')) {
                    $table->index('measured_at', 'travel_times_measured_at_idx');
                }

                if (Schema::hasColumn('travel_times', 'route_name') && ! $this->hasIndex('travel_times', 'travel_times_route_name_idx')) {
                    $table->index('route_name', 'travel_times_route_name_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL only - drop indexes

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if ($this->hasIndex('tasks', 'tasks_status_zone_sla_idx')) {
                    $table->dropIndex('tasks_status_zone_sla_idx');
                }

                if ($this->hasIndex('tasks', 'tasks_slot_status_idx')) {
                    $table->dropIndex('tasks_slot_status_idx');
                }

                if ($this->hasIndex('tasks', 'tasks_assignee_status_idx')) {
                    $table->dropIndex('tasks_assignee_status_idx');
                }

                if ($this->hasIndex('tasks', 'tasks_batch_id_idx')) {
                    $table->dropIndex('tasks_batch_id_idx');
                }
            });
        }

        if (Schema::hasTable('schedule_slots')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                if ($this->hasIndex('schedule_slots', 'schedule_slots_zone_active_idx')) {
                    $table->dropIndex('schedule_slots_zone_active_idx');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if ($this->hasIndex('orders', 'orders_status_payment_idx')) {
                    $table->dropIndex('orders_status_payment_idx');
                }

                if ($this->hasIndex('orders', 'orders_scheduled_at_idx')) {
                    $table->dropIndex('orders_scheduled_at_idx');
                }
            });
        }

        if (Schema::hasTable('traffic_incidents')) {
            Schema::table('traffic_incidents', function (Blueprint $table) {
                if ($this->hasIndex('traffic_incidents', 'traffic_incidents_active_severity_idx')) {
                    $table->dropIndex('traffic_incidents_active_severity_idx');
                }

                if ($this->hasIndex('traffic_incidents', 'traffic_incidents_location_idx')) {
                    $table->dropIndex('traffic_incidents_location_idx');
                }
            });
        }

        if (Schema::hasTable('travel_times')) {
            Schema::table('travel_times', function (Blueprint $table) {
                if ($this->hasIndex('travel_times', 'travel_times_measured_at_idx')) {
                    $table->dropIndex('travel_times_measured_at_idx');
                }

                if ($this->hasIndex('travel_times', 'travel_times_route_name_idx')) {
                    $table->dropIndex('travel_times_route_name_idx');
                }
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $tableName = $connection->getTablePrefix().$table;

        try {
            $doctrineTable = $schemaManager->introspectTable($tableName);
        } catch (\Doctrine\DBAL\Schema\SchemaException $e) {
            return false;
        }

        return $doctrineTable->hasIndex($index);
    }
};
