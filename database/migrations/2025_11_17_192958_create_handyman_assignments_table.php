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
        Schema::create('handyman_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('executor_profile_id')->constrained('executor_profiles')->cascadeOnDelete();
            $table->foreignId('repair_project_id')->nullable()->constrained('repair_projects')->nullOnDelete();
            $table->string('status')->default('proposed'); // 'proposed' | 'accepted' | 'declined' | 'reassigned' | 'cancelled' | 'completed'
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_finish_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_finish_at')->nullable();
            $table->integer('score')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['order_id']);
            $table->index(['executor_profile_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_assignments');
    }
};
