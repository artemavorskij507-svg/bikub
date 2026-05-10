<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_memories')) {
            return;
        }

        Schema::create('agent_memories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('organization_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('agent_steps')->nullOnDelete();

            $table->string('agent_key', 120)->index();
            $table->string('scope', 30)->default('agent')->index(); // agent|run|global
            $table->string('memory_type', 64)->default('note')->index(); // chat_user, chat_system, step_summary, final_report
            $table->string('role', 32)->default('system')->index(); // user|assistant|system|worker

            $table->longText('content');
            $table->text('summary')->nullable();
            $table->unsignedTinyInteger('importance')->default(3)->index();
            $table->unsignedInteger('tokens_estimate')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['organization_id', 'tenant_id', 'agent_key', 'scope', 'created_at'], 'agent_memories_org_tenant_key_scope_created_idx');
            $table->index(['run_id', 'step_id', 'memory_type'], 'agent_memories_run_step_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_memories');
    }
};

