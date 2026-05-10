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
        Schema::create('geo_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('service_area'); // service_area, restricted_area, pickup_point
            $table->decimal('center_latitude', 10, 8); // 68.4378° N
            $table->decimal('center_longitude', 11, 8); // 17.4279° E
            $table->integer('radius_meters')->default(60000); // 60 км радіус
            $table->json('polygon_coordinates')->nullable(); // Для складних геозон
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Додаткові дані
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_zones');
    }
};
