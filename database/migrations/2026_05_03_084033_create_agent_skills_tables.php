<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skills library
        Schema::create('agent_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->text('prompt_template')->nullable();
            $table->json('tools')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['name', 'category']);
        });

        // Agent-to-skill mapping
        Schema::create('agent_skill_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('agent_skills')->onDelete('cascade');
            $table->integer('proficiency_level')->default(3);
            $table->timestamps();
            
            $table->unique(['agent_id', 'skill_id']);
            $table->index('agent_id');
            $table->index('skill_id');
        });

        // AI model configuration per agent
        Schema::create('agent_model_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->string('model_name')->default('sonnet-4.5');
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_tokens')->default(4096);
            $table->text('system_prompt_override')->nullable();
            $table->json('additional_config')->nullable();
            $table->timestamps();
            
            $table->unique('agent_id');
        });

        // Multi-tenant team configurations
        Schema::create('tenant_agent_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->foreignId('director_agent_id')->nullable()->constrained('agency_agents')->onDelete('set null');
            $table->json('active_agents')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('director_agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_agent_teams');
        Schema::dropIfExists('agent_model_configs');
        Schema::dropIfExists('agent_skill_assignments');
        Schema::dropIfExists('agent_skills');
    }
};
