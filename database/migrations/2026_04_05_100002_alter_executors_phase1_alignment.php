<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('executors')) {
            return;
        }

        Schema::table('executors', function (Blueprint $table) {
            if (! Schema::hasColumn('executors', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executors', 'display_name')) {
                $table->string('display_name')->nullable();
            }
            if (! Schema::hasColumn('executors', 'availability_mode')) {
                $table->string('availability_mode', 30)->default('manual');
            }
            if (! Schema::hasColumn('executors', 'home_zone_id')) {
                $table->unsignedBigInteger('home_zone_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executors', 'current_zone_id')) {
                $table->unsignedBigInteger('current_zone_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executors', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable();
            }
            if (! Schema::hasColumn('executors', 'is_dispatchable')) {
                $table->boolean('is_dispatchable')->default(true)->index();
            }
            if (! Schema::hasColumn('executors', 'skills')) {
                $table->jsonb('skills')->nullable();
            }
            if (! Schema::hasColumn('executors', 'capabilities')) {
                $table->jsonb('capabilities')->nullable();
            }
        });

        DB::statement("UPDATE executors SET display_name = COALESCE(display_name, name)");

        if (! $this->hasIndex('executors', 'executors_org_status_dispatchable_idx')) {
            DB::statement('CREATE INDEX executors_org_status_dispatchable_idx ON executors (organization_id, status, is_dispatchable)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('executors')) {
            return;
        }

        if ($this->hasIndex('executors', 'executors_org_status_dispatchable_idx')) {
            DB::statement('DROP INDEX executors_org_status_dispatchable_idx');
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

