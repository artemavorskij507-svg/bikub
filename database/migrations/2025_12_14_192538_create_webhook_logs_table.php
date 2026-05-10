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
        // Drop and recreate if exists (table exists from earlier migration attempts)
        if (Schema::hasTable('webhook_logs')) {
            Schema::drop('webhook_logs');
        }

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();

            // Webhook metadata
            $table->string('provider')->nullable()->index(); // stripe, n8n, sms, internal, etc
            $table->string('event_type')->nullable()->index(); // charge.succeeded, workflow.executed, etc
            $table->string('external_id')->nullable()->index(); // evt_xxx, exec_xxx

            // Status and response
            $table->string('status')->default('received')->index(); // received, processed, failed
            $table->integer('http_status')->nullable(); // Response status from processing

            // Data
            $table->json('payload')->nullable(); // Full webhook payload
            $table->text('error_message')->nullable(); // Error details if failed

            // Tracking
            $table->string('request_id')->nullable()->index(); // Unique request ID for tracing
            $table->timestamp('received_at')->nullable(); // When webhook was received
            $table->timestamp('processed_at')->nullable(); // When webhook was processed
            $table->integer('attempt')->default(0); // Retry attempt number

            // Business entity links
            $table->unsignedBigInteger('order_id')->nullable()->index(); // Link to Order
            $table->unsignedBigInteger('payment_id')->nullable()->index(); // Link to Payment

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
