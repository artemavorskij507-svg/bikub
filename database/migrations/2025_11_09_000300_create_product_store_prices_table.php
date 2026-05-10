<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_store_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedInteger('price')->comment('Price in øre');
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_store_prices');
    }
};
