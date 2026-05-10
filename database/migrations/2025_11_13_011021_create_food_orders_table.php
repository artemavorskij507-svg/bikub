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
        Schema::create('food_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->onDelete('set null');
            $table->json('items')->nullable(); // [{name, quantity, price, notes}]
            $table->text('special_instructions')->nullable();
            $table->json('temperature_requirements')->nullable(); // {hot, cold, frozen}
            $table->json('allergen_info')->nullable(); // {allergens: [], notes: ''}
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_orders');
    }
};
