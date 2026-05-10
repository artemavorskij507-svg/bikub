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
        Schema::create('grocery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('substitution_policy')->default('strict'); // strict, ai, contact
            $table->boolean('is_urgent')->default(false);
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('set null');
            $table->json('preferred_delivery_window')->nullable(); // {start, end}
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
        Schema::dropIfExists('grocery_orders');
    }
};
