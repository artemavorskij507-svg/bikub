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
        Schema::create('road_helper_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('vehicle_type')->nullable(); // car, van, truck
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_number')->nullable(); // номер авто
            $table->json('equipment')->nullable(); // список оборудования (домкрат, OBD2, стартер и т.д.)
            $table->json('skills')->nullable(); // квалификация: jump_start, tire_change, basic_diag, inspection
            $table->enum('current_status', ['offline', 'idle', 'busy', 'on_route'])->default('offline');
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['current_status']);
            $table->index(['location_lat', 'location_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('road_helper_profiles');
    }
};
