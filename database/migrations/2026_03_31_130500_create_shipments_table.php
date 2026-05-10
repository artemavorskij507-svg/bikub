<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipments')) {
            return;
        }

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 64)->unique();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules')->nullOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('origin_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('destination_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('current_route_id')->nullable()->constrained('delivery_routes')->nullOnDelete();
            $table->foreignId('current_geo_zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->foreignId('assigned_personnel_id')->nullable()->constrained('delivery_personnel')->nullOnDelete();
            $table->string('status', 32)->default('created');
            $table->string('priority', 16)->default('normal');
            $table->unsignedInteger('parcel_count')->default(1);
            $table->decimal('total_weight_kg', 10, 3)->nullable();
            $table->decimal('total_volume_m3', 12, 6)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->char('currency', 3)->default('NOK');
            $table->timestamp('promised_delivery_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('external_reference', 80)->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'promised_delivery_at'], 'shipments_status_promised_idx');
            $table->index('order_id', 'shipments_order_idx');
            $table->index('delivery_order_id', 'shipments_delivery_order_idx');
            $table->index(['service_type_id', 'status'], 'shipments_service_status_idx');
            $table->index(['assigned_personnel_id', 'status'], 'shipments_personnel_status_idx');
            $table->index(['current_route_id', 'status'], 'shipments_route_status_idx');
            $table->index(['sender_user_id', 'created_at'], 'shipments_sender_created_idx');
            $table->index(['recipient_user_id', 'created_at'], 'shipments_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
