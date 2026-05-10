<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_routes')) {
            return;
        }

        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_code', 48)->unique();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('origin_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('assigned_personnel_id')->nullable()->constrained('delivery_personnel')->nullOnDelete();
            $table->string('status', 24)->default('planned');
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->decimal('estimated_distance_km', 10, 3)->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->json('waypoints')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'planned_start_at'], 'delivery_routes_status_start_idx');
            $table->index(['assigned_personnel_id', 'status'], 'delivery_routes_personnel_status_idx');
            $table->index(['service_type_id', 'status'], 'delivery_routes_service_status_idx');
            $table->index(['origin_warehouse_id', 'destination_warehouse_id'], 'delivery_routes_wh_pair_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};
