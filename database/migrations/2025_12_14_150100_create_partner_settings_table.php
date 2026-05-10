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
        if (Schema::hasTable('partner_settings')) {
            return;
        }

        Schema::create('partner_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();

            // Notification settings
            $table->string('notification_email')->nullable();
            $table->boolean('sms_notifications_enabled')->default(true);
            $table->boolean('email_notifications_enabled')->default(true);

            // Order settings
            $table->boolean('auto_assign_orders')->default(true);
            $table->integer('max_concurrent_orders')->default(10);
            $table->integer('order_timeout_minutes')->default(30);
            $table->decimal('estimated_delivery_accuracy_km', 8, 2)->default(5.00);
            $table->integer('cancellation_allowed_minutes')->default(10);

            // Rating & pricing
            $table->decimal('rating_minimum_threshold', 3, 2)->default(4.0);
            $table->decimal('emergency_surcharge_percent', 5, 2)->default(0);

            // Operating hours
            $table->time('operating_hours_start')->default('08:00');
            $table->time('operating_hours_end')->default('23:59');

            // Regional settings
            $table->string('timezone')->default('UTC');
            $table->string('language')->default('uk');

            // API settings
            $table->string('api_key')->unique()->nullable();
            $table->string('webhook_url')->nullable();

            // Features
            $table->json('features_enabled')->default(json_encode([]));
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_settings');
    }
};
