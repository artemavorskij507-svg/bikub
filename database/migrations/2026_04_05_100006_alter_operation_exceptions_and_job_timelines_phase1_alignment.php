<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('operation_exceptions')) {
            Schema::table('operation_exceptions', function (Blueprint $table) {
                if (! Schema::hasColumn('operation_exceptions', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
                }
                if (! Schema::hasColumn('operation_exceptions', 'executor_id')) {
                    $table->unsignedBigInteger('executor_id')->nullable()->index();
                }
                if (! Schema::hasColumn('operation_exceptions', 'type')) {
                    $table->string('type', 64)->nullable()->index();
                }
                if (! Schema::hasColumn('operation_exceptions', 'detected_by')) {
                    $table->string('detected_by', 30)->default('system');
                }
                if (! Schema::hasColumn('operation_exceptions', 'owner_user_id')) {
                    $table->unsignedBigInteger('owner_user_id')->nullable()->index();
                }
                if (! Schema::hasColumn('operation_exceptions', 'acknowledged_at')) {
                    $table->timestamp('acknowledged_at')->nullable();
                }
                if (! Schema::hasColumn('operation_exceptions', 'root_cause')) {
                    $table->string('root_cause')->nullable();
                }
                if (! Schema::hasColumn('operation_exceptions', 'resolution_code')) {
                    $table->string('resolution_code')->nullable();
                }
                if (! Schema::hasColumn('operation_exceptions', 'resolution_notes')) {
                    $table->text('resolution_notes')->nullable();
                }
                if (! Schema::hasColumn('operation_exceptions', 'payload')) {
                    $table->jsonb('payload')->nullable();
                }
            });

            DB::statement('UPDATE operation_exceptions SET type = COALESCE(type, exception_type)');
            DB::statement("UPDATE operation_exceptions SET owner_user_id = COALESCE(owner_user_id, owner_id)");
        }

        if (Schema::hasTable('job_timelines') && ! Schema::hasColumn('job_timelines', 'tenant_id')) {
            Schema::table('job_timelines', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // additive-only rollback
    }
};

