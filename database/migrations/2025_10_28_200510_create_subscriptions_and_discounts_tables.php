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
        // Subscription Plans
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('period', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('price', 12, 2);
            $table->json('features')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('plan_id');
            $table->enum('status', ['active', 'cancelled', 'expired', 'paused']);
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
        });

        // Coupons and Promo Codes
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['percent', 'fixed', 'free_delivery', 'first_order']);
            $table->decimal('value', 10, 2);
            $table->integer('max_uses')->nullable();
            $table->integer('used')->default(0);
            $table->decimal('minimum_order_amount', 10, 2)->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->json('applicable_categories')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Service Bundles
        Schema::create('bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('items'); // Array of service items with quantities
            $table->decimal('base_price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Coupon Usage Tracking
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('coupon_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });

        // Bundle Orders
        Schema::create('bundle_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bundle_id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('bundle_price', 12, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
        });

        // Add discount fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('coupon_discount', 10, 2)->default(0);
            $table->decimal('bundle_discount', 10, 2)->default(0);
            $table->uuid('coupon_id')->nullable();
            $table->uuid('bundle_id')->nullable();
            $table->json('discount_breakdown')->nullable();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropForeign(['bundle_id']);
            $table->dropColumn([
                'subtotal', 'discount_amount', 'coupon_discount',
                'bundle_discount', 'coupon_id', 'bundle_id', 'discount_breakdown',
            ]);
        });

        Schema::dropIfExists('bundle_orders');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
