<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to webhook_logs table if they don't exist
        Schema::table('webhook_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_logs', 'provider')) {
                $table->string('provider')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_logs', 'event_type')) {
                $table->string('event_type')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_logs', 'external_id')) {
                $table->string('external_id')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_logs', 'status')) {
                $table->string('status')->default('received')->index(); // received, processed, failed
            }
            if (! Schema::hasColumn('webhook_logs', 'http_status')) {
                $table->integer('http_status')->nullable();
            }
            if (! Schema::hasColumn('webhook_logs', 'payload')) {
                $table->json('payload')->nullable();
            }
            if (! Schema::hasColumn('webhook_logs', 'error_message')) {
                $table->text('error_message')->nullable();
            }
            if (! Schema::hasColumn('webhook_logs', 'request_id')) {
                $table->string('request_id')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_logs', 'received_at')) {
                $table->timestamp('received_at')->nullable();
            }
            if (! Schema::hasColumn('webhook_logs', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
            if (! Schema::hasColumn('webhook_logs', 'attempt')) {
                $table->integer('attempt')->default(0);
            }
            if (! Schema::hasColumn('webhook_logs', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_logs', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // We don't drop columns in down to preserve data
    }
};
