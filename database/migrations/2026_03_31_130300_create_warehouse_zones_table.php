<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_zones')) {
            return;
        }

        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('geo_zone_id')->constrained('geo_zones')->cascadeOnDelete();
            $table->string('coverage_type', 24)->default('primary');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'geo_zone_id', 'coverage_type'],
                'warehouse_zones_scope_unique'
            );
            $table->index(['geo_zone_id', 'coverage_type'], 'warehouse_zones_zone_coverage_idx');
            $table->index(['warehouse_id', 'is_active'], 'warehouse_zones_wh_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};
