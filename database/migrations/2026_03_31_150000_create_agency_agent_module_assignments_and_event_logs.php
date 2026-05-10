<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agency_agent_module_assignments')) {
            Schema::create('agency_agent_module_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agency_agents')->cascadeOnDelete();
                $table->string('module_key', 64);
                $table->string('role', 120);
                $table->string('access_level', 32)->default('observe');
                $table->unsignedInteger('priority')->default(50);
                $table->json('zones')->nullable();
                $table->json('routing_preferences')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['agent_id', 'module_key'], 'agent_module_assignment_unique');
                $table->index(['module_key', 'is_active'], 'agent_module_assignment_module_active_idx');
            });
        }

        if (!Schema::hasTable('agency_agent_event_logs')) {
            Schema::create('agency_agent_event_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agency_agents')->cascadeOnDelete();
                $table->string('module_key', 64);
                $table->string('event_name', 120);
                $table->string('trigger', 120)->nullable();
                $table->foreignId('source_agent_id')->nullable()->constrained('agency_agents')->nullOnDelete();
                $table->string('access_level', 32)->default('observe');
                $table->string('status', 32)->default('received');
                $table->json('payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['module_key', 'event_name'], 'agent_event_logs_module_event_idx');
                $table->index(['agent_id', 'status'], 'agent_event_logs_agent_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_agent_event_logs');
        Schema::dropIfExists('agency_agent_module_assignments');
    }
};
