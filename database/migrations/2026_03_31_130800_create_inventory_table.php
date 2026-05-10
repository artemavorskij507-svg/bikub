<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory')) {
            return;
        }

        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->nullOnDelete();
            $table->string('sku', 80)->nullable();
            $table->string('inventory_type', 24)->default('parcel');
            $table->integer('quantity')->default(1);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(1);
            $table->string('unit', 20)->default('unit');
            $table->string('status', 24)->default('available');
            $table->timestamp('last_movement_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'inventory_wh_status_idx');
            $table->index('parcel_id', 'inventory_parcel_idx');
            $table->index('sku', 'inventory_sku_idx');
            $table->index(['inventory_type', 'status'], 'inventory_type_status_idx');
            $table->index('last_movement_at', 'inventory_last_movement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
