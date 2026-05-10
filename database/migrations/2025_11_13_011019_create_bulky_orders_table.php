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
        Schema::create('bulky_orders', function (Blueprint $table) {
            $table->id();
            $table->json('dimensions')->nullable(); // {length, width, height, volume} in cm
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->json('services')->nullable(); // ['assembly', 'disassembly', 'packaging', 'wrapping']
            $table->boolean('requires_assembly')->default(false);
            $table->boolean('requires_disassembly')->default(false);
            $table->integer('floor_number')->nullable();
            $table->boolean('elevator_available')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulky_orders');
    }
};
