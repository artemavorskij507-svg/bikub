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
        // CMS Pages
        if (! Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->string('title');
                $table->json('content'); // Rich text content with blocks
                $table->json('seo')->nullable(); // SEO metadata
                $table->string('locale', 5)->default('en');
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['slug', 'locale', 'status']);
            });
        }

        // Knowledge Base Articles
        if (! Schema::hasTable('kb_articles')) {
            Schema::create('kb_articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('category');
                $table->string('title');
                $table->json('body'); // Rich text content
                $table->json('tags')->nullable(); // Array of tags
                $table->string('locale', 5)->default('en');
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->integer('views')->default(0);
                $table->integer('helpful_votes')->default(0);
                $table->integer('not_helpful_votes')->default(0);
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['category', 'locale', 'status']);
                // Removed index on JSON column 'tags' for PostgreSQL compatibility
            });
        }

        // FAQ Items
        if (! Schema::hasTable('faq_items')) {
            Schema::create('faq_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('question');
                $table->json('answer'); // Rich text answer
                $table->string('category')->nullable();
                $table->string('locale', 5)->default('en');
                $table->integer('order_no')->default(0);
                $table->boolean('visible')->default(true);
                $table->integer('views')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['category', 'locale', 'visible', 'order_no']);
            });
        }

        // Promo Banners
        if (! Schema::hasTable('promo_banners')) {
            Schema::create('promo_banners', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->json('body'); // Rich text content
                $table->string('placement'); // header, footer, sidebar, popup
                $table->boolean('active')->default(true);
                $table->json('conditions')->nullable(); // Display conditions
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('locale', 5)->default('en');
                $table->integer('priority')->default(100);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['placement', 'active', 'locale']);
                $table->index(['starts_at', 'ends_at', 'active']);
            });
        }

        // Blog/News Articles
        if (! Schema::hasTable('blog_articles')) {
            Schema::create('blog_articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->string('title');
                $table->json('excerpt')->nullable();
                $table->json('content'); // Rich text content
                $table->json('featured_image')->nullable();
                $table->json('tags')->nullable();
                $table->string('locale', 5)->default('en');
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->integer('views')->default(0);
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('seo')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['slug', 'locale', 'status']);
                $table->index(['published_at', 'status']);
            });
        }

        // Content Blocks (Reusable components)
        if (! Schema::hasTable('content_blocks')) {
            Schema::create('content_blocks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('type'); // hero, cta, features, testimonials, etc.
                $table->json('content'); // Block-specific content
                $table->string('locale', 5)->default('en');
                $table->boolean('active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['type', 'locale', 'active']);
            });
        }

        // SEO Settings
        if (! Schema::hasTable('seo_settings')) {
            Schema::create('seo_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('page_type'); // home, catalog, service, article, etc.
                $table->string('page_identifier')->nullable(); // slug or ID
                $table->string('locale', 5)->default('en');
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->text('keywords')->nullable();
                $table->string('canonical_url')->nullable();
                $table->json('og_data')->nullable(); // OpenGraph data
                $table->json('schema_data')->nullable(); // JSON-LD schema
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['page_type', 'page_identifier', 'locale']);
            });
        }

        // Content Categories
        if (! Schema::hasTable('content_categories')) {
            Schema::create('content_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('type'); // kb, blog, faq
                $table->string('locale', 5)->default('en');
                $table->uuid('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();

                // Removed self-referencing FK on parent_id for PostgreSQL compatibility
                $table->index(['type', 'locale', 'active', 'sort_order']);
            });
        }

        // Content Views Tracking
        if (! Schema::hasTable('content_views')) {
            Schema::create('content_views', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('content_type'); // page, article, faq
                $table->uuid('content_id');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('referrer')->nullable();
                $table->timestamp('viewed_at');
                $table->json('meta')->nullable();

                $table->index(['content_type', 'content_id', 'viewed_at']);
                $table->index(['user_id', 'viewed_at']);
            });
        }

        // Content Search Index
        if (! Schema::hasTable('content_search_index')) {
            Schema::create('content_search_index', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('content_type');
                $table->uuid('content_id');
                $table->string('locale', 5);
                $table->text('searchable_text'); // Full-text search content
                $table->json('tags')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['content_type', 'content_id', 'locale']);
                // Keep a simple index; PostgreSQL fulltext handled differently if needed
            });
        }

        // Content Translations
        if (! Schema::hasTable('content_translations')) {
            Schema::create('content_translations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('content_type');
                $table->uuid('content_id');
                $table->string('locale', 5);
                $table->string('field_name'); // title, content, excerpt, etc.
                $table->text('translated_value');
                $table->enum('status', ['draft', 'reviewed', 'approved'])->default('draft');
                $table->foreignId('translator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['content_type', 'content_id', 'locale', 'field_name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_translations');
        Schema::dropIfExists('content_search_index');
        Schema::dropIfExists('content_views');
        Schema::dropIfExists('content_categories');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('blog_articles');
        Schema::dropIfExists('promo_banners');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('cms_pages');
    }
};
