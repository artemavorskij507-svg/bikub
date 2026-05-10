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
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'eta_at')) {
                $table->timestamp('eta_at')->nullable()->after('expected_duration_min');
            }

            if (! Schema::hasColumn('tasks', 'travel_time_min')) {
                $table->integer('travel_time_min')->nullable()->after('eta_at');
            }

            if (! Schema::hasColumn('tasks', 'polyline')) {
                $table->text('polyline')->nullable()->after('travel_time_min');
            }

            if (! Schema::hasColumn('tasks', 'risk_score')) {
                $table->decimal('risk_score', 3, 2)->default(0)->after('polyline');
            }

            if (! Schema::hasColumn('tasks', 'batch_id')) {
                $table->string('batch_id')->nullable()->after('risk_score')->index();
            }

            if (! Schema::hasColumn('tasks', 'suggested_assignee_id')) {
                $table->foreignId('suggested_assignee_id')
                    ->nullable()
                    ->constrained('employees')
                    ->nullOnDelete()
                    ->after('assignee_id');
            }

            if (! Schema::hasColumn('tasks', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)->nullable()->after('suggested_assignee_id');
            }

            if (! Schema::hasColumn('tasks', 'buffer_minutes')) {
                $afterColumn = Schema::hasColumn('tasks', 'sla_deadline_at') ? 'sla_deadline_at' : 'distance_km';
                $table->integer('buffer_minutes')->default(15)->after($afterColumn);
            }
        });

        if (Schema::hasColumns('tasks', ['status', 'sla_deadline_at']) && ! $this->indexExists('tasks', 'tasks_status_sla_deadline_at_index')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['status', 'sla_deadline_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('tasks', 'tasks_status_sla_deadline_at_index')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_status_sla_deadline_at_index');
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'suggested_assignee_id')) {
                $table->dropForeign(['suggested_assignee_id']);
            }

            $columns = [
                'eta_at',
                'travel_time_min',
                'polyline',
                'risk_score',
                'batch_id',
                'suggested_assignee_id',
                'distance_km',
                'buffer_minutes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes($connection->getTablePrefix().$table);

        return array_key_exists($index, $indexes);
    }
};
