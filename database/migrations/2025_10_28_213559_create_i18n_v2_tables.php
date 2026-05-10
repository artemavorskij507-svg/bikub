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
        // Translation Keys Registry
        Schema::create('i18n_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('context')->nullable(); // File or component context
            $table->text('description')->nullable();
            $table->json('parameters')->nullable(); // Expected parameters
            $table->enum('type', ['text', 'plural', 'date', 'number', 'currency'])->default('text');
            $table->boolean('is_required')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['context', 'type']);
        });

        // Translation Values
        Schema::create('i18n_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('key_id');
            $table->string('locale', 5);
            $table->text('value');
            $table->text('plural_forms')->nullable(); // For plural translations
            $table->enum('status', ['draft', 'reviewed', 'approved'])->default('draft');
            $table->foreignId('translator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('key_id')->references('id')->on('i18n_keys')->onDelete('cascade');
            $table->unique(['key_id', 'locale']);
            $table->index(['locale', 'status']);
        });

        // Locale Settings
        Schema::create('i18n_locales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 5)->unique(); // en, no, ru
            $table->string('name'); // English, Norwegian, Russian
            $table->string('native_name'); // English, Norsk, Русский
            $table->string('flag')->nullable(); // Flag emoji or icon
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('fallback_locale', 5)->nullable();
            $table->json('date_format')->nullable();
            $table->json('number_format')->nullable();
            $table->json('currency_format')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Removed self-referencing FK on fallback_locale for PostgreSQL compatibility
        });

        // Currency Settings
        Schema::create('i18n_currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 3)->unique(); // NOK, SEK, EUR, USD
            $table->string('name'); // Norwegian Krone
            $table->string('symbol'); // kr, €, $
            $table->integer('decimal_places')->default(2);
            $table->string('thousands_separator')->default(',');
            $table->string('decimal_separator')->default('.');
            $table->boolean('symbol_before')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Locale-Currency Mapping
        Schema::create('i18n_locale_currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('locale_code', 5);
            $table->string('currency_code', 3);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('locale_code')->references('code')->on('i18n_locales')->onDelete('cascade');
            $table->foreign('currency_code')->references('code')->on('i18n_currencies')->onDelete('cascade');
            $table->unique(['locale_code', 'currency_code']);
        });

        // Translation Statistics
        Schema::create('i18n_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('locale', 5);
            $table->integer('total_keys')->default(0);
            $table->integer('translated_keys')->default(0);
            $table->integer('reviewed_keys')->default(0);
            $table->integer('approved_keys')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->timestamp('last_updated');
            $table->timestamps();

            $table->foreign('locale')->references('code')->on('i18n_locales')->onDelete('cascade');
            $table->index(['locale', 'last_updated']);
        });

        // Translation Comments/Notes
        Schema::create('i18n_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('translation_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->enum('type', ['note', 'question', 'suggestion', 'issue'])->default('note');
            $table->boolean('is_resolved')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('translation_id')->references('id')->on('i18n_translations')->onDelete('cascade');
            $table->index(['translation_id', 'type', 'is_resolved']);
        });

        // Translation History
        Schema::create('i18n_translation_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('translation_id');
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('translation_id')->references('id')->on('i18n_translations')->onDelete('cascade');
            $table->index(['translation_id', 'created_at']);
        });

        // Translation Templates
        Schema::create('i18n_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // email, sms, push, web
            $table->json('keys'); // Array of translation keys used
            $table->string('locale', 5);
            $table->json('content'); // Template content with placeholders
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('locale')->references('code')->on('i18n_locales')->onDelete('cascade');
            $table->index(['type', 'locale', 'is_active']);
        });

        // Translation Jobs (for bulk operations)
        Schema::create('i18n_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // import, export, bulk_translate
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->json('parameters'); // Job parameters
            $table->json('result')->nullable(); // Job result
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i18n_jobs');
        Schema::dropIfExists('i18n_templates');
        Schema::dropIfExists('i18n_translation_history');
        Schema::dropIfExists('i18n_comments');
        Schema::dropIfExists('i18n_stats');
        Schema::dropIfExists('i18n_locale_currencies');
        Schema::dropIfExists('i18n_currencies');
        Schema::dropIfExists('i18n_locales');
        Schema::dropIfExists('i18n_translations');
        Schema::dropIfExists('i18n_keys');
    }
};
