<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_times', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->string('route_name')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->decimal('from_lat', 10, 8)->nullable();
            $table->decimal('from_lng', 11, 8)->nullable();
            $table->decimal('to_lat', 10, 8)->nullable();
            $table->decimal('to_lng', 11, 8)->nullable();
            $table->integer('travel_time_seconds')->nullable(); // Travel time in seconds
            $table->integer('distance_meters')->nullable(); // Distance in meters
            $table->decimal('average_speed_kmh', 6, 2)->nullable(); // Calculated speed
            $table->string('status')->nullable(); // e.g., 'normal', 'delayed', 'congested'
            $table->timestamp('measured_at')->nullable();
            $table->json('geometry')->nullable(); // Route geometry (LineString)
            $table->json('meta')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->unique(['external_id']);
            $table->index(['from_lat', 'from_lng']);
            $table->index(['to_lat', 'to_lng']);
            $table->index('measured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_times');
    }
};
