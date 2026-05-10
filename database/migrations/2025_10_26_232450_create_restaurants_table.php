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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cuisine_type')->nullable(); // pizza, asian, seafood, etc.
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('has_home_delivery')->default(false);
            $table->boolean('has_takeaway')->default(true);
            $table->string('delivery_provider')->nullable(); // internal, wolt, foodora, etc.
            $table->json('opening_hours')->nullable();
            $table->decimal('average_delivery_time_minutes')->nullable();
            $table->decimal('minimum_order_amount', 10, 2)->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->string('delivery_currency', 3)->default('NOK');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
