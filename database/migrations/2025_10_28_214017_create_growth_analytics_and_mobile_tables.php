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
        // Client Events (Growth Analytics)
        Schema::create('client_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('session_id');
            $table->string('event_name'); // view, add_to_cart, begin_checkout, purchase, subscribe, etc.
            $table->json('properties')->nullable(); // Event-specific properties
            $table->timestamp('event_timestamp');
            $table->string('page_url')->nullable();
            $table->string('referrer')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('context')->nullable(); // Additional context data
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_timestamp']);
            $table->index(['session_id', 'event_timestamp']);
            $table->index(['event_name', 'event_timestamp']);
        });

        // User Sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token')->unique();
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->json('utm_data')->nullable(); // UTM parameters
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['session_token', 'last_activity_at']);
        });

        // Conversion Funnels
        Schema::create('conversion_funnels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps'); // Array of funnel steps
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['name', 'is_active']);
        });

        // Funnel Analytics
        Schema::create('funnel_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('funnel_id');
            $table->date('date');
            $table->integer('step_number');
            $table->string('step_name');
            $table->integer('entered_count')->default(0);
            $table->integer('converted_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('drop_off_rate', 5, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('funnel_id')->references('id')->on('conversion_funnels')->onDelete('cascade');
            $table->unique(['funnel_id', 'date', 'step_number']);
            $table->index(['date', 'conversion_rate']);
        });

        // Mobile App Versions
        Schema::create('mobile_app_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('platform', ['android', 'ios'])->default('android');
            $table->string('version');
            $table->string('build_number')->nullable();
            $table->enum('type', ['twa', 'pwa', 'native'])->default('twa');
            $table->text('release_notes')->nullable();
            $table->enum('status', ['draft', 'testing', 'released', 'deprecated'])->default('draft');
            $table->timestamp('released_at')->nullable();
            $table->json('features')->nullable(); // New features in this version
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['platform', 'type', 'status']);
            $table->index(['version', 'released_at']);
        });

        // Mobile App Installs
        Schema::create('mobile_app_installs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id')->nullable();
            $table->enum('platform', ['android', 'ios'])->default('android');
            $table->string('app_version')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('install_source')->nullable(); // Play Store, App Store, direct
            $table->timestamp('installed_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'installed_at']);
            $table->index(['platform', 'installed_at']);
        });

        // PWA Install Prompts
        Schema::create('pwa_install_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->enum('platform', ['android', 'ios', 'desktop'])->default('android');
            $table->enum('action', ['shown', 'accepted', 'dismissed', 'ignored'])->default('shown');
            $table->timestamp('prompted_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'prompted_at']);
            $table->index(['platform', 'action', 'prompted_at']);
        });

        // Mobile App Analytics
        Schema::create('mobile_app_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->enum('platform', ['android', 'ios'])->default('android');
            $table->string('app_version')->nullable();
            $table->integer('active_users')->default(0);
            $table->integer('new_installs')->default(0);
            $table->integer('sessions')->default(0);
            $table->decimal('avg_session_duration', 8, 2)->nullable(); // minutes
            $table->integer('crashes')->default(0);
            $table->decimal('crash_rate', 5, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['date', 'platform', 'app_version']);
            $table->index(['date', 'active_users']);
        });

        // TWA Configuration
        Schema::create('twa_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('package_name')->unique();
            $table->string('app_name');
            $table->string('app_short_name');
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('splash_screen_url')->nullable();
            $table->string('theme_color')->default('#3B82F6');
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#6B7280');
            $table->string('background_color')->default('#FFFFFF');
            $table->string('start_url')->default('/');
            $table->string('scope')->default('/');
            $table->json('display_options')->nullable();
            $table->json('orientation')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['package_name', 'is_active']);
        });

        // Deep Links
        Schema::create('deep_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scheme'); // glfbikube://
            $table->string('host')->nullable();
            $table->string('path')->nullable();
            $table->json('parameters')->nullable();
            $table->string('fallback_url')->nullable();
            $table->enum('platform', ['android', 'ios', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['scheme', 'host', 'path']);
            $table->index(['platform', 'is_active']);
        });

        // Push Notification Tokens
        Schema::create('push_notification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id')->nullable();
            $table->string('token')->unique();
            $table->enum('platform', ['android', 'ios', 'web'])->default('android');
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform', 'is_active']);
            $table->index(['token', 'last_used_at']);
        });

        // Growth Metrics
        Schema::create('growth_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('metric_name'); // dau, mau, retention, ltv, cac, etc.
            $table->decimal('value', 15, 4);
            $table->string('dimension')->nullable(); // platform, country, source, etc.
            $table->string('dimension_value')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['date', 'metric_name', 'dimension', 'dimension_value']);
            $table->index(['date', 'metric_name', 'value']);
        });

        // Cohort Analysis
        Schema::create('cohort_analysis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('cohort_date'); // When users first used the app
            $table->integer('period_number'); // Week/month number since cohort
            $table->integer('users_count')->default(0);
            $table->decimal('retention_rate', 5, 2)->default(0);
            $table->string('cohort_type')->default('monthly'); // daily, weekly, monthly
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['cohort_date', 'period_number', 'cohort_type']);
            $table->index(['cohort_date', 'retention_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohort_analysis');
        Schema::dropIfExists('growth_metrics');
        Schema::dropIfExists('push_notification_tokens');
        Schema::dropIfExists('deep_links');
        Schema::dropIfExists('twa_configurations');
        Schema::dropIfExists('mobile_app_analytics');
        Schema::dropIfExists('pwa_install_prompts');
        Schema::dropIfExists('mobile_app_installs');
        Schema::dropIfExists('mobile_app_versions');
        Schema::dropIfExists('funnel_analytics');
        Schema::dropIfExists('conversion_funnels');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('client_events');
    }
};
