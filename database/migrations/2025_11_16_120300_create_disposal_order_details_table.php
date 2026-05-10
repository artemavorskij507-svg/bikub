<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_order_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->unique();
            $table->json('items'); // [{disposal_item_id, quantity}]
            $table->integer('floor')->nullable();
            $table->boolean('has_elevator')->default(false);
            $table->integer('parking_distance_m')->nullable();
            $table->boolean('requires_dismantling')->default(false);
            $table->boolean('express_requested')->default(false);
            $table->decimal('estimated_volume_m3', 8, 3)->nullable();
            $table->decimal('estimated_weight_kg', 8, 3)->nullable();
            $table->decimal('estimated_price_nok', 10, 2)->nullable();
            $table->foreignId('eco_partner_hint_id')->nullable()->constrained('disposal_partners')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_order_details');
    }
};
