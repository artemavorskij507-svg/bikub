<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sla_policies')) {
            Schema::table('sla_policies', function (Blueprint $table) {
                if (! Schema::hasColumn('sla_policies', 'organization_id')) {
                    $table->uuid('organization_id')->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'service_domain')) {
                    $table->string('service_domain', 50)->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'job_kind')) {
                    $table->string('job_kind', 50)->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'geo_zone_id')) {
                    $table->unsignedBigInteger('geo_zone_id')->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'priority')) {
                    $table->string('priority', 30)->nullable()->index();
                }
                if (! Schema::hasColumn('sla_policies', 'dispatch_within_seconds')) {
                    $table->unsignedInteger('dispatch_within_seconds')->nullable();
                }
                if (! Schema::hasColumn('sla_policies', 'arrival_within_seconds')) {
                    $table->unsignedInteger('arrival_within_seconds')->nullable();
                }
                if (! Schema::hasColumn('sla_policies', 'completion_within_seconds')) {
                    $table->unsignedInteger('completion_within_seconds')->nullable();
                }
                if (! Schema::hasColumn('sla_policies', 'warning_before_seconds')) {
                    $table->unsignedInteger('warning_before_seconds')->default(300);
                }
                if (! Schema::hasColumn('sla_policies', 'breach_strategy')) {
                    $table->jsonb('breach_strategy')->nullable();
                }
            });
        }

        if (! Schema::hasTable('sla_timers')) {
            return;
        }

        Schema::table('sla_timers', function (Blueprint $table) {
            if (! Schema::hasColumn('sla_timers', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'assignment_id')) {
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'sla_policy_id')) {
                $table->unsignedBigInteger('sla_policy_id')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'metric_name')) {
                $table->string('metric_name', 30)->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'target_at')) {
                $table->timestamp('target_at')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'warning_at')) {
                $table->timestamp('warning_at')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'breach_at')) {
                $table->timestamp('breach_at')->nullable()->index();
            }
            if (! Schema::hasColumn('sla_timers', 'status')) {
                $table->string('status', 30)->default('pending')->index();
            }
            if (! Schema::hasColumn('sla_timers', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable();
            }
            if (! Schema::hasColumn('sla_timers', 'context')) {
                $table->jsonb('context')->nullable();
            }
        });

        DB::statement("UPDATE sla_timers SET metric_name = COALESCE(metric_name, 'completion')");
        DB::statement('UPDATE sla_timers SET target_at = COALESCE(target_at, completion_deadline_at, arrival_deadline_at, dispatch_deadline_at)');
        DB::statement('UPDATE sla_timers SET warning_at = COALESCE(warning_at, target_at - interval \'5 minutes\') WHERE target_at IS NOT NULL');
        DB::statement('UPDATE sla_timers SET breach_at = COALESCE(breach_at, target_at) WHERE target_at IS NOT NULL');
    }

    public function down(): void
    {
        // additive-only rollback
    }
};

