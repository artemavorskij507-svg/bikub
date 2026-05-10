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
        // Email Events Tracking
        Schema::create('email_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('message_id')->unique();
            $table->string('email');
            $table->enum('event', ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'unsubscribed'])->default('sent');
            $table->string('template_code')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('event_data')->nullable(); // Event-specific data
            $table->timestamp('event_timestamp');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['email', 'event', 'event_timestamp']);
            $table->index(['template_code', 'event_timestamp']);
        });

        // Email Domains Configuration
        Schema::create('email_domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain')->unique();
            $table->string('name');
            $table->enum('type', ['transactional', 'marketing', 'support'])->default('transactional');
            $table->json('spf_record')->nullable();
            $table->json('dkim_config')->nullable();
            $table->json('dmarc_policy')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Email Suppression Lists
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->enum('type', ['bounce', 'complaint', 'unsubscribe', 'manual'])->default('manual');
            $table->text('reason')->nullable();
            $table->timestamp('suppressed_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['email', 'type']);
            $table->index(['type', 'is_active', 'suppressed_at']);
        });

        // Email Deliverability Reports
        Schema::create('email_deliverability_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('domain');
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_delivered')->default(0);
            $table->integer('emails_bounced')->default(0);
            $table->integer('emails_complained')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);
            $table->decimal('delivery_rate', 5, 2)->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->decimal('complaint_rate', 5, 2)->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['date', 'domain']);
            $table->index(['date', 'delivery_rate']);
        });

        // Legal Documents
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // terms, privacy, cookies, gdpr, etc.
            $table->string('version');
            $table->string('locale', 5)->default('en');
            $table->string('title');
            $table->json('content'); // Rich text content
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'locale', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });

        // User Consents
        Schema::create('user_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('consent_type'); // terms, privacy, cookies, marketing, analytics
            $table->string('version');
            $table->enum('status', ['accepted', 'declined', 'withdrawn'])->default('accepted');
            $table->json('consent_data')->nullable(); // Granular consent data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type', 'status']);
            $table->index(['consent_type', 'consented_at']);
        });

        // Cookie Categories
        Schema::create('cookie_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Cookie Settings
        Schema::create('cookie_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null for anonymous users
            $table->string('session_id')->nullable(); // For anonymous users
            $table->json('accepted_categories'); // Array of accepted category IDs
            $table->json('declined_categories'); // Array of declined category IDs
            $table->timestamp('last_updated');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_updated']);
            $table->index(['session_id', 'last_updated']);
        });

        // Data Processing Activities (GDPR Article 30)
        Schema::create('data_processing_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->json('data_categories'); // Personal data categories
            $table->json('purposes'); // Processing purposes
            $table->json('legal_basis'); // Legal basis for processing
            $table->json('data_subjects'); // Categories of data subjects
            $table->json('recipients'); // Data recipients
            $table->json('transfers'); // Third country transfers
            $table->json('retention_periods'); // Data retention periods
            $table->json('security_measures'); // Security measures
            $table->boolean('is_active')->default(true);
            $table->foreignId('responsible_person')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        // Data Subject Rights Requests
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique(); // DSR-2025-001
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->enum('request_type', ['access', 'rectification', 'erasure', 'portability', 'restriction', 'objection'])->default('access');
            $table->text('description');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('response_data')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'status', 'created_at']);
        });

        // Privacy Impact Assessments (DPIA)
        Schema::create('privacy_impact_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->uuid('processing_activity_id')->nullable();
            $table->enum('status', ['draft', 'in_review', 'approved', 'rejected'])->default('draft');
            $table->json('risk_assessment')->nullable();
            $table->json('mitigation_measures')->nullable();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('processing_activity_id')->references('id')->on('data_processing_activities')->onDelete('set null');
            $table->index(['status', 'assessed_at']);
        });

        // Breach Incidents
        Schema::create('breach_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('incident_number')->unique(); // BREACH-2025-001
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['reported', 'investigating', 'contained', 'resolved'])->default('reported');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamp('discovered_at');
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('affected_data')->nullable();
            $table->json('affected_subjects')->nullable();
            $table->json('containment_measures')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity', 'discovered_at']);
        });

        // Legal Compliance Reports
        Schema::create('compliance_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('report_type'); // gdpr, pci, iso27001, etc.
            $table->string('name');
            $table->text('description');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'archived'])->default('draft');
            $table->json('findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'status', 'prepared_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_reports');
        Schema::dropIfExists('breach_incidents');
        Schema::dropIfExists('privacy_impact_assessments');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('data_processing_activities');
        Schema::dropIfExists('cookie_settings');
        Schema::dropIfExists('cookie_categories');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('email_deliverability_reports');
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_domains');
        Schema::dropIfExists('email_events');
    }
};
