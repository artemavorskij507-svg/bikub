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
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'is_online')) {
                $table->boolean('is_online')->default(false)->after('status');
            }
            if (! Schema::hasColumn('employees', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable()->after('is_online');
            }
            if (! Schema::hasColumn('employees', 'current_zone_id')) {
                $table->foreignId('current_zone_id')->nullable()->constrained('geo_zones')->nullOnDelete()->after('vehicle_type');
            }
            if (! Schema::hasColumn('employees', 'workload_score')) {
                $table->decimal('workload_score', 3, 2)->default(0)->after('current_zone_id');
            }
            if (! Schema::hasColumn('employees', 'last_ping_at')) {
                $table->timestamp('last_ping_at')->nullable()->after('workload_score');
            }

            $table->index(['is_online', 'current_zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['current_zone_id']);
            $table->dropIndex(['is_online', 'current_zone_id']);

            $columns = [
                'is_online', 'vehicle_type', 'current_zone_id',
                'workload_score', 'last_ping_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
