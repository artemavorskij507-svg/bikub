<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_personnel')) {
            return;
        }

        Schema::create('delivery_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('home_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('role', 32)->default('courier');
            $table->string('status', 24)->default('active');
            $table->string('vehicle_type', 40)->nullable();
            $table->decimal('vehicle_capacity_kg', 10, 3)->nullable();
            $table->unsignedInteger('max_parcel_count')->nullable();
            $table->decimal('last_latitude', 10, 8)->nullable();
            $table->decimal('last_longitude', 11, 8)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id', 'delivery_personnel_user_unique');
            $table->unique('employee_id', 'delivery_personnel_employee_unique');
            $table->index(['role', 'status'], 'delivery_personnel_role_status_idx');
            $table->index(['home_warehouse_id', 'status'], 'delivery_personnel_warehouse_status_idx');
            $table->index('last_location_at', 'delivery_personnel_last_location_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_personnel');
    }
};
