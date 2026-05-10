<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_types')) {
            return;
        }

        Schema::table('service_types', function (Blueprint $table) {
            if (!Schema::hasColumn('service_types', 'logistics_enabled')) {
                $table->boolean('logistics_enabled')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('service_types', 'logistics_priority_default')) {
                $table->string('logistics_priority_default', 16)->default('normal')->after('logistics_enabled');
            }
            if (!Schema::hasColumn('service_types', 'logistics_pickup_sla_minutes')) {
                $table->unsignedInteger('logistics_pickup_sla_minutes')->nullable()->after('logistics_priority_default');
            }
            if (!Schema::hasColumn('service_types', 'logistics_delivery_sla_minutes')) {
                $table->unsignedInteger('logistics_delivery_sla_minutes')->nullable()->after('logistics_pickup_sla_minutes');
            }
            if (!Schema::hasColumn('service_types', 'logistics_metadata')) {
                $table->json('logistics_metadata')->nullable()->after('features');
            }
        });

        try {
            Schema::table('service_types', function (Blueprint $table) {
                $table->index(['logistics_enabled', 'is_active'], 'service_types_logistics_enabled_idx');
                $table->index('logistics_priority_default', 'service_types_logistics_priority_idx');
            });
        } catch (\Throwable) {
            // Ignore index creation conflicts caused by pre-existing drift.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_types')) {
            return;
        }

        try {
            Schema::table('service_types', function (Blueprint $table) {
                $table->dropIndex('service_types_logistics_enabled_idx');
                $table->dropIndex('service_types_logistics_priority_idx');
            });
        } catch (\Throwable) {
            // Ignore missing index errors during rollback.
        }

        Schema::table('service_types', function (Blueprint $table) {
            if (Schema::hasColumn('service_types', 'logistics_delivery_sla_minutes')) {
                $table->dropColumn('logistics_delivery_sla_minutes');
            }
            if (Schema::hasColumn('service_types', 'logistics_pickup_sla_minutes')) {
                $table->dropColumn('logistics_pickup_sla_minutes');
            }
            if (Schema::hasColumn('service_types', 'logistics_priority_default')) {
                $table->dropColumn('logistics_priority_default');
            }
            if (Schema::hasColumn('service_types', 'logistics_enabled')) {
                $table->dropColumn('logistics_enabled');
            }
            if (Schema::hasColumn('service_types', 'logistics_metadata')) {
                $table->dropColumn('logistics_metadata');
            }
        });
    }
};
