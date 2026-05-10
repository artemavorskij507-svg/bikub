<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sequence')->default(1);
            $table->string('status')->default('planned');
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_finish_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_finish_at')->nullable();
            $table->integer('progress_percent')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_stages');
    }
};
