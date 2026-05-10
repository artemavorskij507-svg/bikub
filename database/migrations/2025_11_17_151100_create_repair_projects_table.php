<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('project_manager_id')->nullable()->constrained('executor_profiles')->nullOnDelete();
            $table->string('address_line')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_finish_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_finish_at')->nullable();
            $table->bigInteger('budget_estimate_minor')->nullable();
            $table->bigInteger('budget_actual_minor')->nullable();
            $table->string('design_project_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_projects');
    }
};
