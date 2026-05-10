<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parcels')) {
            return;
        }

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('parent_parcel_id')->nullable()->constrained('parcels')->nullOnDelete();
            $table->string('parcel_number', 64)->unique();
            $table->string('barcode', 128)->unique();
            $table->string('status', 24)->default('created');
            $table->decimal('weight_kg', 10, 3);
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('volumetric_weight_kg', 10, 3)->nullable();
            $table->boolean('is_fragile')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->foreignId('current_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('current_geo_zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status'], 'parcels_shipment_status_idx');
            $table->index(['current_warehouse_id', 'status'], 'parcels_wh_status_idx');
            $table->index(['current_geo_zone_id', 'status'], 'parcels_zone_status_idx');
            $table->index('parent_parcel_id', 'parcels_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
