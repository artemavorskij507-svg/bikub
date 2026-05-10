<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_jobs')) {
            return;
        }

        Schema::table('service_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('service_jobs', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'job_kind')) {
                $table->string('job_kind', 50)->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'executor_id')) {
                $table->unsignedBigInteger('executor_id')->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'assignment_id')) {
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
            }

            if (! Schema::hasColumn('service_jobs', 'pickup_lat')) {
                $table->decimal('pickup_lat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'pickup_lng')) {
                $table->decimal('pickup_lng', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'dropoff_lat')) {
                $table->decimal('dropoff_lat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'dropoff_lng')) {
                $table->decimal('dropoff_lng', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'service_lat')) {
                $table->decimal('service_lat', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'service_lng')) {
                $table->decimal('service_lng', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('service_jobs', 'promised_eta_at')) {
                $table->timestamp('promised_eta_at')->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'promised_completion_at')) {
                $table->timestamp('promised_completion_at')->nullable()->index();
            }
            if (! Schema::hasColumn('service_jobs', 'price_snapshot')) {
                $table->jsonb('price_snapshot')->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });

        if (! $this->hasIndex('service_jobs', 'service_jobs_org_kind_priority_idx')) {
            DB::statement('CREATE INDEX service_jobs_org_kind_priority_idx ON service_jobs (organization_id, job_kind, priority)');
        }

        if (! $this->hasIndex('service_jobs', 'service_jobs_source_type_source_id_idx')) {
            DB::statement('CREATE INDEX service_jobs_source_type_source_id_idx ON service_jobs (source_type, source_id)');
        }

        DB::statement("UPDATE service_jobs SET job_kind = COALESCE(job_kind, job_type, 'visit')");
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_jobs')) {
            return;
        }

        if ($this->hasIndex('service_jobs', 'service_jobs_org_kind_priority_idx')) {
            DB::statement('DROP INDEX service_jobs_org_kind_priority_idx');
        }
        if ($this->hasIndex('service_jobs', 'service_jobs_source_type_source_id_idx')) {
            DB::statement('DROP INDEX service_jobs_source_type_source_id_idx');
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $result = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1",
            [$table, $index]
        );

        return (bool) $result;
    }
};

