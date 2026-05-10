<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('assignments', 'eta_at')) {
                $table->timestamp('eta_at')->nullable();
            }
            if (! Schema::hasColumn('assignments', 'arrival_deadline_at')) {
                $table->timestamp('arrival_deadline_at')->nullable();
            }
            if (! Schema::hasColumn('assignments', 'completion_deadline_at')) {
                $table->timestamp('completion_deadline_at')->nullable();
            }
            if (! Schema::hasColumn('assignments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('assignments', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable();
            }
        });

        if (! $this->hasIndex('assignments', 'assignments_executor_status_idx')) {
            DB::statement('CREATE INDEX assignments_executor_status_idx ON assignments (executor_id, status)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        if ($this->hasIndex('assignments', 'assignments_executor_status_idx')) {
            DB::statement('DROP INDEX assignments_executor_status_idx');
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

