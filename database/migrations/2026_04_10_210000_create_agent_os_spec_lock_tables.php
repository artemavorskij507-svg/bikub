<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_runs')) {
            Schema::create('agent_runs', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('status', 40)->default('queued')->index();
                $table->string('risk_level', 20)->default('medium')->index();
                $table->boolean('requires_approval')->default(false)->index();
                $table->boolean('deployment_allowed')->default(false)->index();
                $table->string('idempotency_key', 191)->nullable();
                $table->text('goal')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();

                $table->index(['organization_id', 'tenant_id', 'status'], 'agent_runs_org_tenant_status_idx');
                $table->index(['organization_id', 'tenant_id', 'risk_level'], 'agent_runs_org_tenant_risk_idx');
                $table->unique(['organization_id', 'tenant_id', 'idempotency_key'], 'agent_runs_org_tenant_idempotency_uq');
            });
        }

        if (! Schema::hasTable('agent_steps')) {
            Schema::create('agent_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
                $table->foreignId('parent_step_id')->nullable()->constrained('agent_steps')->nullOnDelete();
                $table->uuid('organization_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('step_type', 64)->index();
                $table->string('name', 191)->nullable();
                $table->string('status', 40)->default('queued')->index();
                $table->boolean('is_risky')->default(false)->index();
                $table->json('depends_on')->nullable();
                $table->json('input_payload')->nullable();
                $table->json('output_payload')->nullable();
                $table->json('artifact_contract')->nullable();
                $table->text('validation_notes')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->unsignedInteger('max_retries')->default(0);
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('heartbeat_at')->nullable()->index();
                $table->timestamp('timeout_at')->nullable()->index();
                $table->timestamp('finished_at')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['run_id', 'status'], 'agent_steps_run_status_idx');
                $table->index(['organization_id', 'tenant_id', 'status'], 'agent_steps_org_tenant_status_idx');
            });
        }

        if (! Schema::hasTable('agent_artifacts')) {
            Schema::create('agent_artifacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
                $table->foreignId('step_id')->nullable()->constrained('agent_steps')->nullOnDelete();
                $table->uuid('organization_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('artifact_type', 64)->index();
                $table->string('path')->nullable();
                $table->longText('content')->nullable();
                $table->string('validation_status', 32)->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('agent_validations')) {
            Schema::create('agent_validations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->constrained('agent_runs')->cascadeOnDelete();
                $table->foreignId('step_id')->nullable()->constrained('agent_steps')->nullOnDelete();
                $table->foreignId('artifact_id')->nullable()->constrained('agent_artifacts')->nullOnDelete();
                $table->string('validator_type', 64)->index();
                $table->string('result', 32)->index();
                $table->decimal('score', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_validations');
        Schema::dropIfExists('agent_artifacts');
        Schema::dropIfExists('agent_steps');
        Schema::dropIfExists('agent_runs');
    }
};
