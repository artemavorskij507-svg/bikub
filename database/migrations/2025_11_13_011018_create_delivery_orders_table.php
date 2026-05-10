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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('type'); // grocery, bulky, food
            $table->json('pickup_location')->nullable(); // {lat, lng, address}
            $table->json('delivery_location')->nullable(); // {lat, lng, address}
            $table->text('pickup_address')->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->timestamp('eta')->nullable();
            $table->timestamp('actual_delivery_time')->nullable();
            $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('courier_location')->nullable(); // {lat, lng, updated_at}
            $table->string('tracking_status')->default('pending'); // pending, assigned, picked_up, in_transit, delivered, cancelled
            $table->string('substitution_policy')->nullable(); // strict, ai, contact
            $table->boolean('is_urgent')->default(false);
            $table->json('metadata')->nullable();

            // Polymorphic relationship
            $table->nullableMorphs('orderable'); // orderable_type, orderable_id

            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id']);
            $table->index(['type', 'tracking_status']);
            $table->index(['courier_id', 'tracking_status']);
            $table->index('eta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
