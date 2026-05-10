<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eco_teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('vehicle_type')->nullable(); // van, truck_small, truck_large
            $table->decimal('vehicle_capacity_m3', 8, 3)->nullable();
            $table->decimal('vehicle_max_weight_kg', 8, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_teams');
    }
};
