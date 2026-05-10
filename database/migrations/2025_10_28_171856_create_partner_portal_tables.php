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
        // Partner Users (roles for partners)
        Schema::create('partner_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role'); // admin/manager/staff
            $table->timestamps();

            $table->unique(['partner_id', 'user_id']);
            $table->index(['partner_id', 'role']);
        });

        // Partner Settings
        Schema::create('partner_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->json('data'); // часы, blackout dates, min order, lead time
            $table->timestamps();

            $table->unique('partner_id');
        });

        // Partner Service Areas
        Schema::create('partner_service_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('geo_zones')->onDelete('cascade');
            $table->integer('capacity')->default(0);
            $table->decimal('surcharge', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['partner_id', 'zone_id']);
            $table->index('partner_id');
        });

        // Partner Pricing Overrides
        Schema::create('partner_pricing_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained('service_types')->onDelete('cascade');
            $table->json('rule'); // per_km, prep_time, packaging_fee...
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_to')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'service_type_id']);
            $table->index(['active_from', 'active_to']);
        });

        // Partner Payout Accounts
        Schema::create('partner_payout_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('partner_id');
        });

        // Partner Statements
        Schema::create('partner_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('status')->default('draft'); // draft/approved/paid
            $table->json('breakdown')->nullable(); // детализация по заказам
            $table->timestamps();

            $table->index(['partner_id', 'period_start', 'period_end']);
            $table->index('status');
        });

        // Import Sources
        Schema::create('import_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('type'); // csv/xlsx/api
            $table->string('name');
            $table->json('config'); // маппинг полей, настройки
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('partner_id');
        });

        // Import Jobs
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_id');
            $table->string('status')->default('queued'); // queued/running/done/failed
            $table->json('stats')->nullable(); // статистика импорта
            $table->text('error')->nullable();
            $table->json('preview_data')->nullable(); // предпросмотр изменений
            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('import_sources')->onDelete('cascade');
            $table->index(['source_id', 'status']);
            $table->index('created_at');
        });

        // Notification Templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code'); // order_created, courier_assigned, eta_changed...
            $table->string('channel'); // email/sms/push
            $table->string('locale'); // ru/no/en
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // доступные переменные
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'channel', 'locale']);
        });

        // Notification Events
        Schema::create('notification_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->string('channel'); // email/sms/push
            $table->string('template_code');
            $table->json('payload'); // данные для шаблона
            $table->string('status')->default('queued'); // queued/sent/failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('created_at');
        });

        // Notification Preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('channel'); // email/sms/push
            $table->string('locale')->default('no');
            $table->boolean('enabled')->default(true);
            $table->json('meta')->nullable(); // дополнительные настройки
            $table->timestamps();

            $table->unique(['user_id', 'channel']);
            $table->index('user_id');
        });

        // Update existing tables
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('external_id')->nullable();
            $table->string('source')->nullable();
            $table->string('hash')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->index('external_id');
            $table->index('source');
        });

        Schema::table('retail_stores', function (Blueprint $table) {
            $table->string('external_id')->nullable();
            $table->string('source')->nullable();
            $table->string('hash')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->index('external_id');
            $table->index('source');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_provider')->default('stripe'); // stripe/vipps
            $table->string('payment_provider_ref')->nullable(); // vipps order id
            $table->json('payment_meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'payment_provider_ref', 'payment_meta']);
        });

        Schema::table('retail_stores', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['external_id', 'source', 'hash', 'last_seen_at']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['external_id', 'source', 'hash', 'last_seen_at']);
        });

        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('import_sources');
        Schema::dropIfExists('partner_statements');
        Schema::dropIfExists('partner_payout_accounts');
        Schema::dropIfExists('partner_pricing_overrides');
        Schema::dropIfExists('partner_service_areas');
        Schema::dropIfExists('partner_settings');
        Schema::dropIfExists('partner_users');
    }
};
