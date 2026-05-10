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
        // BankID/ID-porten SSO
        if (! Schema::hasTable('oidc_providers')) {
            Schema::create('oidc_providers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name'); // bankid, id_porten
                $table->string('issuer_url');
                $table->string('client_id');
                $table->text('client_secret');
                $table->json('scopes');
                $table->json('claims_mapping');
                $table->boolean('is_active');
                $table->timestamps();

                $table->unique(['name']);
                $table->index(['is_active']);
            });
        }

        // OIDC Sessions
        if (! Schema::hasTable('oidc_sessions')) {
            Schema::create('oidc_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('provider_id');
                $table->string('state');
                $table->string('nonce');
                $table->string('code_challenge');
                $table->string('code_challenge_method');
                $table->json('claims');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['user_id']);
                $table->index(['state']);
                $table->index(['expires_at']);
            });
        }

        // SSO Audit Log
        if (! Schema::hasTable('sso_audit_log')) {
            Schema::create('sso_audit_log', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('action'); // login, logout, token_refresh, role_change
                $table->uuid('provider_id');
                $table->string('ip_address');
                $table->string('user_agent');
                $table->json('metadata');
                $table->timestamp('timestamp');
                $table->timestamps();

                $table->index(['user_id', 'timestamp']);
                $table->index(['action', 'timestamp']);
            });
        }

        // On-call Rotations
        if (! Schema::hasTable('oncall_rotations')) {
            Schema::create('oncall_rotations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('team'); // noc, devops, security
                $table->json('schedule'); // cron expression, timezone
                $table->json('contacts'); // phone, email, slack
                $table->json('escalation_rules');
                $table->boolean('active');
                $table->timestamps();

                $table->index(['team', 'active']);
            });
        }

        // On-call Assignments
        if (! Schema::hasTable('oncall_assignments')) {
            Schema::create('oncall_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('rotation_id');
                $table->uuid('user_id');
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->string('status'); // active, completed, overridden
                $table->timestamps();

                $table->index(['rotation_id', 'starts_at']);
                $table->index(['user_id']);
            });
        }

        // Post-mortems
        if (! Schema::hasTable('postmortems')) {
            Schema::create('postmortems', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('incident_id');
                $table->text('summary');
                $table->text('root_cause');
                $table->json('timeline');
                $table->json('actions'); // immediate, short_term, long_term
                $table->json('lessons_learned');
                $table->string('status'); // draft, review, published
                $table->uuid('author_id');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['incident_id']);
                $table->index(['status']);
            });
        }

        // Incident Response Actions
        if (! Schema::hasTable('incident_response_actions')) {
            Schema::create('incident_response_actions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('incident_id');
                $table->string('action_type'); // page, escalate, communicate
                $table->json('action_data');
                $table->string('status'); // pending, completed, failed
                $table->timestamp('triggered_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['incident_id']);
                $table->index(['status']);
            });
        }

        // Budget Limits
        if (! Schema::hasTable('budget_limits')) {
            Schema::create('budget_limits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('scope'); // api, storage, compute, email
                $table->decimal('monthly_limit', 12, 2);
                $table->decimal('alert_threshold', 5, 2); // percentage
                $table->json('alert_recipients');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['scope', 'is_active']);
            });
        }

        // Cost Snapshots
        if (! Schema::hasTable('cost_snapshots')) {
            Schema::create('cost_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('scope');
                $table->date('period');
                $table->decimal('cost', 12, 2);
                $table->json('breakdown'); // by service, region, etc.
                $table->json('metadata');
                $table->timestamps();

                $table->index(['scope', 'period']);
            });
        }

        // Cost Alerts
        if (! Schema::hasTable('cost_alerts')) {
            Schema::create('cost_alerts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('budget_limit_id');
                $table->decimal('current_cost', 12, 2);
                $table->decimal('threshold_percentage', 5, 2);
                $table->string('severity'); // warning, critical
                $table->string('status'); // open, acknowledged, resolved
                $table->timestamp('triggered_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['budget_limit_id']);
                $table->index(['status']);
            });
        }

        // Chaos Experiments
        if (! Schema::hasTable('chaos_experiments')) {
            Schema::create('chaos_experiments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code'); // redis_down, db_failover, osrm_503
                $table->string('name');
                $table->text('description');
                $table->text('hypothesis');
                $table->json('schedule'); // cron expression
                $table->json('parameters');
                $table->string('status'); // draft, scheduled, running, completed, failed
                $table->json('results')->nullable();
                $table->timestamp('last_run')->nullable();
                $table->timestamps();

                $table->unique(['code']);
                $table->index(['status']);
            });
        }

        // Chaos Experiment Runs
        if (! Schema::hasTable('chaos_experiment_runs')) {
            Schema::create('chaos_experiment_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('experiment_id');
                $table->string('status'); // running, completed, failed, aborted
                $table->json('metrics_before');
                $table->json('metrics_during');
                $table->json('metrics_after');
                $table->text('observations');
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['experiment_id']);
                $table->index(['started_at']);
            });
        }

        // Archive Policies
        if (! Schema::hasTable('archive_policies')) {
            Schema::create('archive_policies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('entity'); // orders, payments, logs
                $table->integer('ttl_days');
                $table->string('storage_class'); // standard, ia, glacier
                $table->boolean('legal_hold');
                $table->json('conditions'); // additional rules
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['entity', 'is_active']);
            });
        }

        // Legal Holds
        if (! Schema::hasTable('legal_holds')) {
            Schema::create('legal_holds', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('entity');
                $table->uuid('target_id');
                $table->text('reason');
                $table->uuid('placed_by');
                $table->timestamp('placed_at');
                $table->timestamp('released_at')->nullable();
                $table->uuid('released_by')->nullable();
                $table->timestamps();

                $table->index(['entity', 'target_id']);
                $table->index(['placed_at']);
            });
        }

        // Archive Jobs
        if (! Schema::hasTable('archive_jobs')) {
            Schema::create('archive_jobs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('policy_id');
                $table->string('entity');
                $table->json('criteria');
                $table->string('status'); // pending, running, completed, failed
                $table->integer('records_processed');
                $table->integer('records_archived');
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['policy_id']);
                $table->index(['status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_jobs');
        Schema::dropIfExists('legal_holds');
        Schema::dropIfExists('archive_policies');
        Schema::dropIfExists('chaos_experiment_runs');
        Schema::dropIfExists('chaos_experiments');
        Schema::dropIfExists('cost_alerts');
        Schema::dropIfExists('cost_snapshots');
        Schema::dropIfExists('budget_limits');
        Schema::dropIfExists('incident_response_actions');
        Schema::dropIfExists('postmortems');
        Schema::dropIfExists('oncall_assignments');
        Schema::dropIfExists('oncall_rotations');
        Schema::dropIfExists('sso_audit_log');
        Schema::dropIfExists('oidc_sessions');
        Schema::dropIfExists('oidc_providers');
    }
};
