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
        Schema::create('office_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 10);
            $table->string('color', 7);
            $table->integer('x_min');
            $table->integer('x_max');
            $table->integer('y_min');
            $table->integer('y_max');
            $table->integer('capacity')->default(10);
            $table->json('amenities')->default('[]');
            $table->timestamps();
            
            $table->index('slug');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_zones');
    }
};
