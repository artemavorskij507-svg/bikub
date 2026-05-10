<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('executor_locations')) {
            return;
        }

        Schema::table('executor_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('executor_locations', 'organization_id')) {
                $table->uuid('organization_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executor_locations', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executor_locations', 'assignment_id')) {
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executor_locations', 'service_job_id')) {
                $table->unsignedBigInteger('service_job_id')->nullable()->index();
            }
            if (! Schema::hasColumn('executor_locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('executor_locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('executor_locations', 'speed')) {
                $table->decimal('speed', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('executor_locations', 'accuracy')) {
                $table->decimal('accuracy', 8, 2)->nullable();
            }
        });

        DB::statement('UPDATE executor_locations SET latitude = COALESCE(latitude, lat), longitude = COALESCE(longitude, lng), speed = COALESCE(speed, speed_kmh)');
    }

    public function down(): void
    {
        // additive-only rollback
    }
};

