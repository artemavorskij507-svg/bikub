<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('color')->default('gray');
            $table->string('emoji')->default('🤖');
            $table->text('vibe')->nullable();
            $table->text('identity_memory')->nullable();
            $table->text('core_mission')->nullable();
            $table->text('critical_rules')->nullable();
            $table->text('technical_deliverables')->nullable();
            $table->text('workflow_process')->nullable();
            $table->text('success_metrics')->nullable();
            $table->string('status')->default('idle');
            $table->timestamp('last_active_at')->nullable();
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->string('current_task')->nullable();
            
            // 2D Office specific fields
            $table->string('current_zone')->default('workspace');
            $table->string('target_zone')->nullable();
            $table->decimal('position_x', 10, 2)->default(0);
            $table->decimal('position_y', 10, 2)->default(0);
            $table->string('avatar_sprite')->nullable();
            $table->string('avatar_direction')->default('down');
            $table->boolean('is_moving')->default(false);
            $table->json('movement_path')->nullable();
            $table->string('current_activity')->default('idle');
            $table->string('status_message')->nullable();
            
            $table->string('avatar_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
            $table->index('current_zone');
            $table->index('performance_score');
            $table->index('is_moving');
        });

        Schema::create('agency_agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->string('category')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('target_zone')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->integer('progress')->default(0);
            $table->text('result')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->json('dependencies')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });

        Schema::create('agency_agent_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->foreignId('receiver_agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->string('message_type')->default('message');
            $table->text('content');
            $table->string('status')->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('priority')->default('normal');
            $table->foreignId('related_task_id')->nullable()->constrained('agency_agent_tasks')->onDelete('set null');
            $table->timestamps();

            $table->index('sender_agent_id');
            $table->index('receiver_agent_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('agency_agent_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->string('metric_type');
            $table->decimal('value', 15, 4);
            $table->string('unit');
            $table->timestamp('recorded_at');
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('metric_type');
            $table->index('recorded_at');
        });

        Schema::create('agency_office_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('icon');
            $table->string('color');
            $table->json('bounds');
            $table->integer('capacity')->default(20);
            $table->integer('current_occupancy')->default(0);
            $table->json('amenities')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('agency_agent_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agency_agents')->onDelete('cascade');
            $table->string('activity_type');
            $table->string('zone');
            $table->text('description')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('activity_type');
            $table->index('zone');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_agent_activities');
        Schema::dropIfExists('agency_office_zones');
        Schema::dropIfExists('agency_agent_metrics');
        Schema::dropIfExists('agency_agent_communications');
        Schema::dropIfExists('agency_agent_tasks');
        Schema::dropIfExists('agency_agents');
    }
};
