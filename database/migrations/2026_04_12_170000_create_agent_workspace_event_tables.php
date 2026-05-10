<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_run_threads')) {
            Schema::create('agent_run_threads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
                $table->uuid('organization_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('thread_key', 50);
                $table->string('title', 120);
                $table->boolean('is_system')->default(false)->index();
                $table->unsignedSmallInteger('sort_order')->default(100)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['run_id', 'thread_key'], 'agent_run_threads_run_thread_uq');
                $table->index(['organization_id', 'tenant_id', 'run_id'], 'agent_run_threads_org_tenant_run_idx');
            });
        }

        if (! Schema::hasTable('agent_run_events')) {
            Schema::create('agent_run_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
                $table->foreignId('step_id')->nullable()->constrained('agent_steps')->nullOnDelete();
                $table->foreignId('thread_id')->nullable()->constrained('agent_run_threads')->nullOnDelete();
                $table->uuid('organization_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('event_type', 64)->index();
                $table->string('event_level', 20)->nullable()->index();
                $table->string('actor_type', 32)->nullable()->index();
                $table->string('actor_key', 120)->nullable()->index();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->string('dedupe_key', 120)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['run_id', 'id'], 'agent_run_events_run_id_idx');
                $table->index(['run_id', 'event_type'], 'agent_run_events_run_type_idx');
                $table->unique(['run_id', 'dedupe_key'], 'agent_run_events_run_dedupe_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_run_events');
        Schema::dropIfExists('agent_run_threads');
    }
};
