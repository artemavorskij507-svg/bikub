<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tracking_events')) {
            return;
        }

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('delivery_routes')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('delivery_personnel')->nullOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->string('event_type', 48);
            $table->string('event_status', 16)->default('success');
            $table->timestamp('event_time');
            $table->string('source_system', 32)->default('api');
            $table->string('source_event_id', 96)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['source_system', 'source_event_id'], 'tracking_events_source_unique');
            $table->index(['shipment_id', 'event_time'], 'tracking_events_shipment_time_idx');
            $table->index(['parcel_id', 'event_time'], 'tracking_events_parcel_time_idx');
            $table->index(['route_id', 'event_time'], 'tracking_events_route_time_idx');
            $table->index(['warehouse_id', 'event_time'], 'tracking_events_wh_time_idx');
            $table->index(['event_type', 'event_time'], 'tracking_events_type_time_idx');
            $table->index(['source_system', 'event_time'], 'tracking_events_source_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
