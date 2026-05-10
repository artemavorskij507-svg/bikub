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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('polygon'); // polygon, circle, bbox
            $table->decimal('center_lat', 10, 8)->nullable();
            $table->decimal('center_lng', 11, 8)->nullable();
            $table->decimal('radius_km', 8, 3)->nullable(); // for circle type
            $table->json('geometry_data')->nullable(); // Geometry data as JSON instead of PostGIS
            $table->json('coordinates')->nullable(); // Additional coordinate data
            $table->boolean('is_active')->default(true);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->integer('delivery_time_minutes')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
