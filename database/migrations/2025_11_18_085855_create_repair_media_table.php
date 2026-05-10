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
        Schema::create('repair_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('repair_stage_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('repair_update_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type', 32)->default('photo');
            $table->string('role', 32)->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('caption')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['repair_project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_media');
    }
};
