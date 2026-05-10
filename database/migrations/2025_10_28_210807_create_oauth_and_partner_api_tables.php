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
        // OAuth2/OIDC Clients
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret');
            $table->json('scopes')->nullable(); // Array of scopes
            $table->json('redirect_uris')->nullable(); // Array of allowed redirect URIs
            $table->enum('grant_type', ['client_credentials', 'authorization_code', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
        });

        // OAuth2 Access Tokens
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // For authorization_code flow
            $table->string('token')->unique();
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
            $table->index(['token', 'expires_at']);
        });

        // Webhook Subscriptions
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('url');
            $table->string('secret');
            $table->json('events'); // Array of subscribed events
            $table->boolean('active')->default(true);
            $table->integer('timeout_seconds')->default(30);
            $table->integer('retry_attempts')->default(3);
            $table->integer('retry_delay_seconds')->default(60);
            $table->timestamp('last_triggered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'active']);
        });

        // Webhook Delivery Logs
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->string('event');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->integer('attempt')->default(1);
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('webhook_subscriptions')->onDelete('cascade');
            $table->index(['subscription_id', 'event', 'created_at']);
        });

        // KYC Documents
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->enum('type', [
                'business_registration', 'tax_certificate', 'bank_statement',
                'id_document', 'address_proof', 'insurance_certificate',
            ]);
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->string('file_url');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'type', 'status']);
        });

        // Partner Contracts
        Schema::create('partner_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('version');
            $table->enum('status', ['draft', 'pending', 'signed', 'expired', 'terminated'])->default('draft');
            $table->text('contract_text');
            $table->string('file_url')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('signature_data')->nullable(); // E-signature data
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });

        // Dynamic Pricing Rules
        Schema::create('pricing_context_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->uuid('org_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('conditions'); // DSL conditions
            $table->json('actions'); // Pricing actions (surge, discount, etc.)
            $table->integer('priority')->default(100); // Lower = higher priority
            $table->boolean('active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['service_type_id', 'active', 'priority']);
        });

        // A/B Experiments
        Schema::create('ab_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('variants'); // Array of variant configurations
            $table->json('traffic_allocation'); // Traffic split between variants
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('success_metrics'); // Metrics to track success
            $table->json('guardrail_metrics'); // Metrics to prevent negative impact
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['code', 'status']);
        });

        // A/B Assignments
        Schema::create('ab_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('experiment_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('variant');
            $table->timestamp('assigned_at');
            $table->json('metadata')->nullable();

            $table->foreign('experiment_id')->references('id')->on('ab_experiments')->onDelete('cascade');
            $table->unique(['experiment_id', 'user_id']);
            $table->unique(['experiment_id', 'partner_id']);
        });

        // Pricing Calculation Logs
        Schema::create('pricing_calculation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->json('input_context'); // Input parameters
            $table->json('applied_rules'); // Rules that were applied
            $table->json('calculation_steps'); // Step-by-step calculation
            $table->decimal('base_price', 12, 2);
            $table->decimal('final_price', 12, 2);
            $table->decimal('surge_multiplier', 8, 4)->default(1.0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });

        // Telemetry Events
        Schema::create('telemetry_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('resource_id'); // Order, Task, or Device ID
            $table->string('resource_type'); // order, task, device
            $table->string('event_type'); // location, speed, fuel, dtc_code, etc.
            $table->json('data'); // Event-specific data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable(); // GPS accuracy in meters
            $table->decimal('speed', 8, 2)->nullable(); // Speed in km/h
            $table->decimal('heading', 8, 2)->nullable(); // Direction in degrees
            $table->timestamp('event_timestamp');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['resource_id', 'resource_type', 'event_timestamp']);
            $table->index(['event_type', 'event_timestamp']);
        });

        // Geofences
        Schema::create('geofences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['warehouse', 'partner', 'customer', 'restricted', 'custom']);
            $table->json('area'); // GeoJSON polygon
            $table->decimal('radius_meters')->nullable(); // For circular geofences
            $table->boolean('active')->default(true);
            $table->json('triggers'); // Events to trigger (enter, exit, dwell)
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['code', 'active']);
            $table->index(['type', 'active']);
        });

        // Geofence Events
        Schema::create('geofence_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('geofence_id');
            $table->uuid('resource_id');
            $table->string('resource_type');
            $table->enum('event_type', ['enter', 'exit', 'dwell']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('event_timestamp');
            $table->integer('dwell_time_seconds')->nullable(); // For dwell events
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('geofence_id')->references('id')->on('geofences')->onDelete('cascade');
            $table->index(['geofence_id', 'resource_id', 'event_timestamp']);
        });

        // API Rate Limiting
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('endpoint');
            $table->integer('requests_count');
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
            $table->index(['client_id', 'endpoint', 'window_start']);
            $table->index(['ip_address', 'endpoint', 'window_start']);
        });

        // API Audit Logs
        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->nullable();
            $table->string('ip_address');
            $table->string('method');
            $table->string('endpoint');
            $table->integer('status_code');
            $table->integer('response_time_ms');
            $table->integer('request_size_bytes');
            $table->integer('response_size_bytes');
            $table->string('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
            $table->index(['client_id', 'created_at']);
            $table->index(['endpoint', 'status_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_audit_logs');
        Schema::dropIfExists('api_rate_limits');
        Schema::dropIfExists('geofence_events');
        Schema::dropIfExists('geofences');
        Schema::dropIfExists('telemetry_events');
        Schema::dropIfExists('pricing_calculation_logs');
        Schema::dropIfExists('ab_assignments');
        Schema::dropIfExists('ab_experiments');
        Schema::dropIfExists('pricing_context_rules');
        Schema::dropIfExists('partner_contracts');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_clients');
    }
};
