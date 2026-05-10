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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('office_zones')->onDelete('cascade');
            $table->integer('x_position')->default(0);
            $table->integer('y_position')->default(0);
            $table->string('avatar')->nullable();
            $table->string('emoji', 10)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('source_file')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('name');
            $table->index('category_id');
            $table->index('zone_id');
            $table->index('is_active');
            $table->index(['x_position', 'y_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
