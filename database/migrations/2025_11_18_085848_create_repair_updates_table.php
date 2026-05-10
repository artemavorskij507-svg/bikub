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
        Schema::create('repair_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('repair_stage_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('author_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type', 32)->default('note');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status_snapshot')->nullable();
            $table->unsignedTinyInteger('progress_percent')->nullable();
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
        Schema::dropIfExists('repair_updates');
    }
};
