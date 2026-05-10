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
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('task_description');
            $table->json('orchestrator_decision')->nullable(); // Which agents were summoned
            $table->enum('status', ['pending', 'in_progress', 'awaiting_approval', 'completed', 'failed'])->default('pending');
            $table->text('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
    }
};
