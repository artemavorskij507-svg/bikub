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
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->integer('capacity')->default(0)->after('max_orders');
            $table->integer('booked')->default(0)->after('capacity');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('checkin_at')->nullable()->after('completed_at');
            $table->timestamp('checkout_at')->nullable()->after('checkin_at');
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->uuid('vehicle_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id');
            $table->foreignId('order_id');
            $table->integer('seq');
            $table->timestamp('eta')->nullable();
            $table->decimal('eta_confidence', 3, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index(['route_id', 'seq']);
            $table->index('order_id');
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 128)->unique();
            $table->string('provider', 32);
            $table->string('type', 64);
            $table->json('payload');
            $table->string('status', 32)->default('processed');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at', 'status']);
        });

        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('routes');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['checkin_at', 'checkout_at']);
        });

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'booked']);
        });
    }
};
