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
        Schema::create('moving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moving_order_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category')->comment('living_room, bedroom, kitchen, office, etc.');
            $table->decimal('volume', 8, 2)->comment('m³');
            $table->decimal('weight', 8, 2)->comment('kg');
            $table->boolean('requires_assembly')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('moving_order_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_items');
    }
};
