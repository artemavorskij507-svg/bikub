<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_notifications')) {
            return;
        }

        Schema::create('delivery_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained('parcels')->nullOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 24)->default('sms');
            $table->string('notification_type', 48)->default('status_update');
            $table->string('status', 24)->default('pending');
            $table->string('recipient', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status'], 'delivery_notifications_shipment_status_idx');
            $table->index(['user_id', 'status'], 'delivery_notifications_user_status_idx');
            $table->index(['channel', 'status'], 'delivery_notifications_channel_status_idx');
            $table->index('delivery_order_id', 'delivery_notifications_order_idx');
            $table->index('created_at', 'delivery_notifications_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notifications');
    }
};
