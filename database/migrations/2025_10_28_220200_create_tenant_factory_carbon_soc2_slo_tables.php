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
        // Tenant Factory Templates
        if (! Schema::hasTable('tenant_templates')) {
            Schema::create('tenant_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('code');
                $table->json('modules'); // care, eco, tow, market, food
                $table->string('locale');
                $table->json('branding_config');
                $table->json('feature_flags');
                $table->json('default_settings');
                $table->boolean('is_active');
                $table->timestamps();

                $table->unique(['code']);
                $table->index(['is_active']);
            });
        }

        // Tenant Deployments
        if (! Schema::hasTable('tenant_deployments')) {
            Schema::create('tenant_deployments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_code');
                $table->uuid('template_id');
                $table->string('status'); // deploying, active, failed, suspended
                $table->json('deployment_config');
                $table->json('infrastructure_data');
                $table->text('deployment_log')->nullable();
                $table->timestamp('deployed_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_code']);
                $table->index(['status']);
            });
        }

        // Tenant Infrastructure
        if (! Schema::hasTable('tenant_infrastructure')) {
            Schema::create('tenant_infrastructure', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_deployment_id');
                $table->string('resource_type'); // subdomain, s3_bucket, cdn, queue
                $table->string('resource_name');
                $table->json('resource_config');
                $table->string('status');
                $table->timestamps();

                $table->index(['tenant_deployment_id', 'resource_type']);
            });
        }

        // Carbon Footprint Tracking
        if (! Schema::hasTable('carbon_footprint')) {
            Schema::create('carbon_footprint', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_id')->nullable();
                $table->uuid('route_id')->nullable();
                $table->string('transport_type'); // bike, car, electric_car, public_transport
                $table->decimal('distance_km', 8, 3);
                $table->decimal('co2_grams', 10, 2);
                $table->decimal('emission_factor', 8, 4); // grams CO2 per km
                $table->json('calculation_method');
                $table->timestamps();

                $table->index(['order_id']);
                $table->index(['route_id']);
                $table->index(['transport_type']);
            });
        }

        // ESG Reports
        if (! Schema::hasTable('esg_reports')) {
            Schema::create('esg_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('report_type'); // monthly, quarterly, annual
                $table->date('report_period_start');
                $table->date('report_period_end');
                $table->json('environmental_metrics');
                $table->json('social_metrics');
                $table->json('governance_metrics');
                $table->string('status'); // draft, published, archived
                $table->timestamps();

                $table->index(['report_type', 'report_period_start']);
            });
        }

        // Eco Routes Configuration
        if (! Schema::hasTable('eco_routes_config')) {
            Schema::create('eco_routes_config', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->json('optimization_criteria'); // min_co2, min_time, balanced
                $table->json('transport_preferences');
                $table->decimal('co2_weight', 3, 2);
                $table->decimal('time_weight', 3, 2);
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['is_active']);
            });
        }

        // Carbon Offset Projects
        if (! Schema::hasTable('carbon_offset_projects')) {
            Schema::create('carbon_offset_projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type'); // reforestation, renewable_energy, energy_efficiency
                $table->text('description');
                $table->decimal('co2_offset_per_unit', 10, 2);
                $table->decimal('cost_per_unit', 10, 2);
                $table->string('currency', 3);
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        }

        // SOC2 Controls
        if (! Schema::hasTable('soc2_controls')) {
            Schema::create('soc2_controls', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('control_id');
                $table->string('control_name');
                $table->text('description');
                $table->string('control_type'); // preventive, detective, corrective
                $table->string('category'); // access, availability, processing_integrity, confidentiality, privacy
                $table->json('implementation_details');
                $table->string('status'); // implemented, partially_implemented, not_implemented
                $table->timestamps();

                $table->unique(['control_id']);
                $table->index(['category', 'status']);
            });
        }

        // SOC2 Evidence
        if (! Schema::hasTable('soc2_evidence')) {
            Schema::create('soc2_evidence', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('control_id');
                $table->string('evidence_type'); // screenshot, log, policy, procedure
                $table->string('file_path');
                $table->text('description');
                $table->timestamp('collected_at');
                $table->uuid('collected_by');
                $table->timestamps();

                $table->index(['control_id']);
                $table->index(['evidence_type']);
            });
        }

        // SLO Definitions
        if (! Schema::hasTable('slo_definitions')) {
            Schema::create('slo_definitions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('service_name');
                $table->string('sli_name'); // availability, latency, error_rate
                $table->decimal('slo_target', 5, 3); // 99.9, 0.1, etc.
                $table->string('measurement_window'); // 30d, 7d, 1d
                $table->json('alerting_rules');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['service_name', 'is_active']);
            });
        }

        // SLO Events
        if (! Schema::hasTable('slo_events')) {
            Schema::create('slo_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('component');
                $table->string('sli');
                $table->decimal('value', 8, 3);
                $table->string('status'); // success, failure, degraded
                $table->timestamp('ts');
                $table->json('meta');
                $table->timestamps();

                $table->index(['component', 'sli', 'ts']);
            });
        }

        // Error Budget Tracking
        if (! Schema::hasTable('error_budgets')) {
            Schema::create('error_budgets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('slo_id');
                $table->date('budget_period');
                $table->decimal('budget_remaining', 8, 3);
                $table->decimal('burn_rate', 8, 3);
                $table->string('status'); // healthy, warning, critical
                $table->timestamps();

                $table->index(['slo_id', 'budget_period']);
            });
        }

        // AIOps Alerts
        if (! Schema::hasTable('aiops_alerts')) {
            Schema::create('aiops_alerts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('alert_type'); // anomaly, threshold, pattern
                $table->string('severity'); // low, medium, high, critical
                $table->string('component');
                $table->text('description');
                $table->json('alert_data');
                $table->string('status'); // open, acknowledged, resolved
                $table->timestamp('triggered_at');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'severity']);
                $table->index(['component', 'triggered_at']);
            });
        }

        // Auto Remediation Actions
        if (! Schema::hasTable('auto_remediation_actions')) {
            Schema::create('auto_remediation_actions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('alert_id');
                $table->string('action_type'); // feature_rollback, scale_up, circuit_breaker
                $table->json('action_config');
                $table->string('status'); // pending, executing, completed, failed
                $table->text('result')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();

                $table->index(['alert_id']);
                $table->index(['status']);
            });
        }

        // Vulnerability Reports
        if (! Schema::hasTable('vulnerability_reports')) {
            Schema::create('vulnerability_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('source'); // dependabot, composer_audit, manual
                $table->string('package');
                $table->string('version');
                $table->string('severity'); // low, medium, high, critical
                $table->text('description');
                $table->string('status'); // open, in_progress, resolved, false_positive
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->json('meta');
                $table->timestamps();

                $table->index(['severity', 'status']);
                $table->index(['package', 'version']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vulnerability_reports');
        Schema::dropIfExists('auto_remediation_actions');
        Schema::dropIfExists('aiops_alerts');
        Schema::dropIfExists('error_budgets');
        Schema::dropIfExists('slo_events');
        Schema::dropIfExists('slo_definitions');
        Schema::dropIfExists('soc2_evidence');
        Schema::dropIfExists('soc2_controls');
        Schema::dropIfExists('carbon_offset_projects');
        Schema::dropIfExists('eco_routes_config');
        Schema::dropIfExists('esg_reports');
        Schema::dropIfExists('carbon_footprint');
        Schema::dropIfExists('tenant_infrastructure');
        Schema::dropIfExists('tenant_deployments');
        Schema::dropIfExists('tenant_templates');
    }
};
