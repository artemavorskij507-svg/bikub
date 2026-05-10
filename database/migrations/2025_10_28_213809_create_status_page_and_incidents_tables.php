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
        // Status Components
        Schema::create('status_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // api, storefront, payments, webhooks, websockets
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['operational', 'degraded', 'partial_outage', 'major_outage', 'maintenance'])->default('operational');
            $table->uuid('group_id')->nullable(); // Component group
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->json('monitoring_config')->nullable(); // Monitoring configuration
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'sort_order']);
            $table->index(['status', 'is_public']);
        });

        // Status Component Groups
        Schema::create('status_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Incidents
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique(); // INC-2025-001
            $table->string('title');
            $table->json('description'); // Rich text description
            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved'])->default('investigating');
            $table->enum('impact', ['none', 'minor', 'major', 'critical'])->default('minor');
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('affected_components')->nullable(); // Array of component IDs
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'impact', 'started_at']);
        });

        // Incident Updates
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id');
            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved'])->default('investigating');
            $table->json('message'); // Rich text update
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('notify_subscribers')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('incident_id')->references('id')->on('incidents')->onDelete('cascade');
            $table->index(['incident_id', 'created_at']);
        });

        // Scheduled Maintenance
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->json('description'); // Rich text description
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('impact', ['none', 'minor', 'major', 'critical'])->default('minor');
            $table->json('affected_components')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('notify_subscribers')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_start']);
        });

        // Status Subscribers
        Schema::create('status_subscribers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('subscription_preferences')->nullable(); // Email preferences
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['email', 'is_active']);
        });

        // Status Notifications
        Schema::create('status_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscriber_id')->nullable();
            $table->string('email')->nullable(); // For non-subscriber notifications
            $table->enum('type', ['incident', 'maintenance', 'resolution'])->default('incident');
            $table->uuid('incident_id')->nullable();
            $table->uuid('maintenance_id')->nullable();
            $table->string('subject');
            $table->json('content'); // Rich text content
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('subscriber_id')->references('id')->on('status_subscribers')->onDelete('cascade');
            $table->foreign('incident_id')->references('id')->on('incidents')->onDelete('cascade');
            $table->foreign('maintenance_id')->references('id')->on('maintenance_windows')->onDelete('cascade');
            $table->index(['type', 'status', 'created_at']);
        });

        // Status Page Settings
        Schema::create('status_page_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site_name')->default('GLF BiKube Status');
            $table->text('site_description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#6B7280');
            $table->json('social_links')->nullable();
            $table->json('contact_info')->nullable();
            $table->boolean('show_uptime_stats')->default(true);
            $table->boolean('show_incident_history')->default(true);
            $table->boolean('allow_subscriptions')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Uptime Statistics
        Schema::create('uptime_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('component_id')->nullable();
            $table->date('date');
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->integer('total_checks')->default(0);
            $table->integer('successful_checks')->default(0);
            $table->integer('failed_checks')->default(0);
            $table->decimal('avg_response_time', 8, 2)->nullable(); // milliseconds
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('component_id')->references('id')->on('status_components')->onDelete('cascade');
            $table->unique(['component_id', 'date']);
            $table->index(['date', 'uptime_percentage']);
        });

        // Status Checks (Health checks)
        Schema::create('status_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('component_id');
            $table->string('name');
            $table->enum('type', ['http', 'https', 'ping', 'tcp', 'ssl', 'dns'])->default('http');
            $table->string('url')->nullable();
            $table->integer('timeout_seconds')->default(30);
            $table->integer('check_interval_seconds')->default(300); // 5 minutes
            $table->json('expected_response')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_check_at')->nullable();
            $table->enum('last_status', ['up', 'down', 'unknown'])->default('unknown');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('component_id')->references('id')->on('status_components')->onDelete('cascade');
            $table->index(['component_id', 'is_active']);
        });

        // Status Check Results
        Schema::create('status_check_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('check_id');
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('checked_at');
            $table->json('meta')->nullable();

            $table->foreign('check_id')->references('id')->on('status_checks')->onDelete('cascade');
            $table->index(['check_id', 'checked_at']);
            $table->index(['status', 'checked_at']);
        });

        // Status Page Analytics
        Schema::create('status_page_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->integer('page_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('subscriptions')->default(0);
            $table->integer('unsubscriptions')->default(0);
            $table->json('referrers')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['date']);
            $table->index(['date', 'page_views']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_page_analytics');
        Schema::dropIfExists('status_check_results');
        Schema::dropIfExists('status_checks');
        Schema::dropIfExists('uptime_stats');
        Schema::dropIfExists('status_page_settings');
        Schema::dropIfExists('status_notifications');
        Schema::dropIfExists('status_subscribers');
        Schema::dropIfExists('maintenance_windows');
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('status_groups');
        Schema::dropIfExists('status_components');
    }
};
