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
        Schema::table('errand_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('errand_tasks', 'order_id')) {
                $table->foreignId('order_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('errand_tasks', 'title')) {
                $table->string('title')
                    ->after('order_id');
            }

            if (! Schema::hasColumn('errand_tasks', 'category')) {
                $table->string('category', 64)
                    ->nullable()
                    ->after('title');
            }

            if (! Schema::hasColumn('errand_tasks', 'sub_category')) {
                $table->string('sub_category', 64)
                    ->nullable()
                    ->after('category');
            }

            if (! Schema::hasColumn('errand_tasks', 'status')) {
                $table->string('status', 32)
                    ->default('pending')
                    ->after('sub_category');
            }

            if (! Schema::hasColumn('errand_tasks', 'priority')) {
                $table->string('priority', 32)
                    ->nullable()
                    ->after('status');
            }

            if (! Schema::hasColumn('errand_tasks', 'customer_name')) {
                $table->string('customer_name', 120)
                    ->nullable()
                    ->after('priority');
            }

            if (! Schema::hasColumn('errand_tasks', 'customer_phone')) {
                $table->string('customer_phone', 32)
                    ->nullable()
                    ->after('customer_name');
            }

            if (! Schema::hasColumn('errand_tasks', 'pickup_address')) {
                $table->string('pickup_address')
                    ->nullable()
                    ->after('customer_phone');
            }

            if (! Schema::hasColumn('errand_tasks', 'dropoff_address')) {
                $table->string('dropoff_address')
                    ->nullable()
                    ->after('pickup_address');
            }

            if (! Schema::hasColumn('errand_tasks', 'pickup_location')) {
                $table->jsonb('pickup_location')
                    ->nullable()
                    ->after('dropoff_address');
            }

            if (! Schema::hasColumn('errand_tasks', 'dropoff_location')) {
                $table->jsonb('dropoff_location')
                    ->nullable()
                    ->after('pickup_location');
            }

            if (! Schema::hasColumn('errand_tasks', 'waypoints')) {
                $table->jsonb('waypoints')
                    ->nullable()
                    ->after('dropoff_location');
            }

            if (! Schema::hasColumn('errand_tasks', 'contacts')) {
                $table->jsonb('contacts')
                    ->nullable()
                    ->after('waypoints');
            }

            if (! Schema::hasColumn('errand_tasks', 'notes')) {
                $table->text('notes')
                    ->nullable()
                    ->after('contacts');
            }

            if (! Schema::hasColumn('errand_tasks', 'is_urgent')) {
                $table->boolean('is_urgent')
                    ->default(false)
                    ->after('notes');
            }

            if (! Schema::hasColumn('errand_tasks', 'requires_signature')) {
                $table->boolean('requires_signature')
                    ->default(false)
                    ->after('is_urgent');
            }

            if (! Schema::hasColumn('errand_tasks', 'requires_trusted_helper')) {
                $table->boolean('requires_trusted_helper')
                    ->default(false)
                    ->after('requires_signature');
            }

            if (! Schema::hasColumn('errand_tasks', 'requires_document_handling')) {
                $table->boolean('requires_document_handling')
                    ->default(false)
                    ->after('requires_trusted_helper');
            }

            if (! Schema::hasColumn('errand_tasks', 'expected_duration_minutes')) {
                $table->unsignedInteger('expected_duration_minutes')
                    ->nullable()
                    ->after('requires_document_handling');
            }

            if (! Schema::hasColumn('errand_tasks', 'expected_distance_km')) {
                $table->decimal('expected_distance_km', 8, 2)
                    ->nullable()
                    ->after('expected_duration_minutes');
            }

            if (! Schema::hasColumn('errand_tasks', 'complexity_level')) {
                $table->unsignedTinyInteger('complexity_level')
                    ->default(1)
                    ->after('expected_distance_km');
            }

            if (! Schema::hasColumn('errand_tasks', 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')
                    ->default(0)
                    ->after('complexity_level');
            }

            if (! Schema::hasColumn('errand_tasks', 'material_advance_amount')) {
                $table->unsignedInteger('material_advance_amount')
                    ->default(0)
                    ->after('risk_score');
            }

            if (! Schema::hasColumn('errand_tasks', 'executor_profile_id')) {
                $table->foreignId('executor_profile_id')
                    ->nullable()
                    ->after('material_advance_amount')
                    ->constrained('executor_profiles')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('errand_tasks', 'geo_zone_id')) {
                $table->foreignId('geo_zone_id')
                    ->nullable()
                    ->after('executor_profile_id')
                    ->constrained('geo_zones')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('errand_tasks', 'scheduled_at')) {
                $table->timestamp('scheduled_at')
                    ->nullable()
                    ->after('geo_zone_id');
            }

            if (! Schema::hasColumn('errand_tasks', 'completed_at')) {
                $table->timestamp('completed_at')
                    ->nullable()
                    ->after('scheduled_at');
            }

            if (! Schema::hasColumn('errand_tasks', 'pricing_snapshot')) {
                $table->jsonb('pricing_snapshot')
                    ->nullable()
                    ->after('completed_at');
            }

            if (! Schema::hasColumn('errand_tasks', 'meta')) {
                $table->jsonb('meta')
                    ->nullable()
                    ->after('pricing_snapshot');
            }

            if (! $this->hasIndex('errand_tasks', 'errand_tasks_status_index')) {
                $table->index('status', 'errand_tasks_status_index');
            }

            if (! $this->hasIndex('errand_tasks', 'errand_tasks_category_index')) {
                $table->index('category', 'errand_tasks_category_index');
            }

            if (! $this->hasIndex('errand_tasks', 'errand_tasks_geo_zone_id_index')) {
                $table->index('geo_zone_id', 'errand_tasks_geo_zone_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('errand_tasks', function (Blueprint $table) {
            if ($this->hasIndex('errand_tasks', 'errand_tasks_status_index')) {
                $table->dropIndex('errand_tasks_status_index');
            }

            if ($this->hasIndex('errand_tasks', 'errand_tasks_category_index')) {
                $table->dropIndex('errand_tasks_category_index');
            }

            if ($this->hasIndex('errand_tasks', 'errand_tasks_geo_zone_id_index')) {
                $table->dropIndex('errand_tasks_geo_zone_id_index');
            }

            $foreignColumns = [
                'geo_zone_id',
                'executor_profile_id',
                'order_id',
            ];

            foreach ($foreignColumns as $column) {
                if (Schema::hasColumn('errand_tasks', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            $columns = [
                'title',
                'category',
                'sub_category',
                'status',
                'priority',
                'customer_name',
                'customer_phone',
                'pickup_address',
                'dropoff_address',
                'pickup_location',
                'dropoff_location',
                'waypoints',
                'contacts',
                'notes',
                'is_urgent',
                'requires_signature',
                'requires_trusted_helper',
                'requires_document_handling',
                'expected_duration_minutes',
                'expected_distance_km',
                'complexity_level',
                'risk_score',
                'material_advance_amount',
                'scheduled_at',
                'completed_at',
                'pricing_snapshot',
                'meta',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('errand_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            [$table, $indexName]
        );

        return count($indexes) > 0;
    }
};
