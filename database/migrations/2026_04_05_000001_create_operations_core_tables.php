<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_jobs')) {
            Schema::create('service_jobs', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->string('source_type', 64)->default('order');
                $table->unsignedBigInteger('source_id')->nullable()->index();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
                $table->string('service_domain', 64)->index();
                $table->string('job_type', 64)->nullable()->index();
                $table->string('status', 32)->default('pending')->index();
                $table->string('priority', 32)->default('normal')->index();
                $table->json('pickup_point')->nullable();
                $table->json('dropoff_point')->nullable();
                $table->json('service_point')->nullable();
                $table->timestamp('time_window_start')->nullable();
                $table->timestamp('time_window_end')->nullable();
                $table->unsignedInteger('service_duration_minutes')->nullable();
                $table->json('required_skills')->nullable();
                $table->json('required_capacity')->nullable();
                $table->json('required_equipment')->nullable();
                $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
                $table->foreignId('schedule_slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
                $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
                $table->timestamp('customer_eta_at')->nullable();
                $table->unsignedInteger('promised_sla_minutes')->nullable();
                $table->timestamp('actual_started_at')->nullable();
                $table->timestamp('actual_completed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['organization_id', 'status', 'service_domain'], 'service_jobs_org_status_domain_idx');
            });
        }

        if (! Schema::hasTable('executors')) {
            Schema::create('executors', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('code', 64)->nullable()->unique();
                $table->string('name');
                $table->string('executor_type', 32)->default('employee')->index();
                $table->string('status', 32)->default('offline')->index();
                $table->unsignedSmallInteger('max_concurrent_jobs')->default(1);
                $table->json('capacity')->nullable();
                $table->json('equipment')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['organization_id', 'status'], 'executors_org_status_idx');
            });
        }

        if (! Schema::hasTable('executor_skills')) {
            Schema::create('executor_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('executor_id')->constrained('executors')->cascadeOnDelete();
                $table->string('skill_code', 64)->index();
                $table->unsignedTinyInteger('skill_level')->default(1);
                $table->timestamps();
                $table->unique(['executor_id', 'skill_code']);
            });
        }

        if (! Schema::hasTable('executor_shifts')) {
            Schema::create('executor_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('executor_id')->constrained('executors')->cascadeOnDelete();
                $table->date('shift_date')->index();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->boolean('is_available')->default(true)->index();
                $table->timestamps();
                $table->index(['executor_id', 'shift_date'], 'executor_shifts_executor_date_idx');
            });
        }

        if (! Schema::hasTable('executor_locations')) {
            Schema::create('executor_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('executor_id')->constrained('executors')->cascadeOnDelete();
                $table->decimal('lat', 11, 8);
                $table->decimal('lng', 11, 8);
                $table->decimal('speed_kmh', 8, 2)->nullable();
                $table->unsignedSmallInteger('heading')->nullable();
                $table->timestamp('recorded_at')->index();
                $table->timestamps();
                $table->index(['executor_id', 'recorded_at'], 'executor_locations_executor_recorded_idx');
            });
        }

        if (! Schema::hasTable('dispatch_runs')) {
            Schema::create('dispatch_runs', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->nullOnDelete();
                $table->string('mode', 32)->default('auto_assign')->index();
                $table->string('status', 32)->default('queued')->index();
                $table->json('filters')->nullable();
                $table->json('summary')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dispatch_candidates')) {
            Schema::create('dispatch_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispatch_run_id')->constrained('dispatch_runs')->cascadeOnDelete();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->foreignId('executor_id')->constrained('executors')->cascadeOnDelete();
                $table->boolean('eligible')->default(false)->index();
                $table->decimal('score', 10, 4)->default(0);
                $table->json('score_breakdown')->nullable();
                $table->json('ineligibility_reasons')->nullable();
                $table->timestamps();
                $table->index(['dispatch_run_id', 'service_job_id', 'score'], 'dispatch_candidates_rank_idx');
            });
        }

        if (! Schema::hasTable('assignments')) {
            Schema::create('assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->foreignId('executor_id')->constrained('executors')->cascadeOnDelete();
                $table->foreignId('dispatch_run_id')->nullable()->constrained('dispatch_runs')->nullOnDelete();
                $table->string('assignment_mode', 32)->default('auto_assign')->index();
                $table->string('status', 32)->default('assigned')->index();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('arrived_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('route_plan')->nullable();
                $table->json('metadata')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['service_job_id', 'status'], 'assignments_job_status_idx');
            });
        }

        if (! Schema::hasTable('job_state_transitions')) {
            Schema::create('job_state_transitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->string('event_type', 64)->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_type', 64)->nullable();
                $table->text('note')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('transitioned_at')->index();
                $table->timestamps();
                $table->index(['service_job_id', 'transitioned_at'], 'job_transitions_job_time_idx');
            });
        }

        if (! Schema::hasTable('sla_timers')) {
            Schema::create('sla_timers', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->timestamp('dispatch_deadline_at')->nullable()->index();
                $table->timestamp('arrival_deadline_at')->nullable()->index();
                $table->timestamp('completion_deadline_at')->nullable()->index();
                $table->string('dispatch_state', 24)->default('ok');
                $table->string('arrival_state', 24)->default('ok');
                $table->string('completion_state', 24)->default('ok');
                $table->timestamp('last_evaluated_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('operation_exceptions')) {
            Schema::create('operation_exceptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
                $table->string('exception_type', 64)->index();
                $table->string('severity', 16)->default('medium')->index();
                $table->string('status', 32)->default('open')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->timestamp('detected_at')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->text('summary')->nullable();
                $table->json('remediation')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_exceptions');
        Schema::dropIfExists('sla_timers');
        Schema::dropIfExists('job_state_transitions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('dispatch_candidates');
        Schema::dropIfExists('dispatch_runs');
        Schema::dropIfExists('executor_locations');
        Schema::dropIfExists('executor_shifts');
        Schema::dropIfExists('executor_skills');
        Schema::dropIfExists('executors');
        Schema::dropIfExists('service_jobs');
    }
};

