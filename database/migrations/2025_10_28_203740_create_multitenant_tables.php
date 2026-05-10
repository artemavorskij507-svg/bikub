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
        // Organizations (Tenants)
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('domain')->nullable(); // Custom domain
                $table->string('subdomain')->nullable(); // Subdomain for multi-tenant
                $table->text('description')->nullable();
                $table->string('logo_url')->nullable();
                $table->json('branding')->nullable(); // Colors, fonts, etc.
                $table->json('features')->nullable(); // Enabled modules/features
                $table->json('settings')->nullable(); // Organization-specific settings
                $table->enum('status', ['active', 'suspended', 'trial', 'cancelled'])->default('active');
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamps();
            });
        }

        // Organization Settings
        if (! Schema::hasTable('organization_settings')) {
            Schema::create('organization_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id')->unique();
                $table->json('branding')->default('{}'); // Colors, logo, fonts
                $table->json('features')->default('{}'); // Enabled modules
                $table->json('policies')->default('{}'); // Return policy, terms, etc.
                $table->json('integrations')->default('{}'); // Third-party integrations
                $table->timestamps();

                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            });
        }

        // Organization Users (Tenant-specific user roles)
        if (! Schema::hasTable('organization_users')) {
            Schema::create('organization_users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('role', ['owner', 'admin', 'manager', 'staff', 'viewer']);
                $table->json('permissions')->nullable(); // Custom permissions
                $table->boolean('is_active')->default(true);
                $table->timestamp('joined_at');
                $table->timestamps();

                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

                $table->unique(['organization_id', 'user_id']);
            });
        }

        // Add org_id to existing tables
        if (! Schema::hasColumn('users', 'default_org_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('default_org_id')->nullable();
                $table->foreign('default_org_id')->references('id')->on('organizations')->onDelete('set null');
            });
        }

        if (! Schema::hasColumn('orders', 'org_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->uuid('org_id')->nullable();
                $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index('org_id');
            });
        }

        if (! Schema::hasColumn('partners', 'org_id')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->uuid('org_id')->nullable();
                $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index('org_id');
            });
        }

        if (! Schema::hasColumn('service_types', 'org_id')) {
            Schema::table('service_types', function (Blueprint $table) {
                $table->uuid('org_id')->nullable();
                $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index('org_id');
            });
        }

        if (! Schema::hasColumn('pricing_rules', 'org_id')) {
            Schema::table('pricing_rules', function (Blueprint $table) {
                $table->uuid('org_id')->nullable();
                $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index('org_id');
            });
        }

        if (! Schema::hasColumn('geo_zones', 'org_id')) {
            Schema::table('geo_zones', function (Blueprint $table) {
                $table->uuid('org_id')->nullable();
                $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
                $table->index('org_id');
            });
        }

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->uuid('org_id');
            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index('org_id');
        });

        // Search and Facets
        Schema::create('search_indexes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->string('entity_type'); // 'service', 'product', 'partner'
            $table->uuid('entity_id');
            $table->string('title');
            $table->text('content');
            $table->json('facets')->nullable(); // Category, price range, zone, etc.
            $table->json('metadata')->nullable(); // Additional searchable data
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['org_id', 'entity_type', 'entity_id']);
        });

        // Search Synonyms
        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->string('language', 2); // 'ru', 'no', 'en'
            $table->string('term');
            $table->json('synonyms'); // Array of synonyms
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['org_id', 'language', 'term']);
        });

        // Geo v3 - Enhanced routing
        Schema::table('routes', function (Blueprint $table) {
            $table->string('profile')->default('summer'); // summer, winter, storm
            $table->json('weather_conditions')->nullable();
            $table->decimal('turn_penalty', 8, 2)->default(0); // Cost for turns
            $table->decimal('left_turn_penalty', 8, 2)->default(0); // Cost for left turns
            $table->boolean('prioritize_highways')->default(true);
        });

        Schema::table('route_stops', function (Blueprint $table) {
            $table->timestamp('window_from')->nullable();
            $table->timestamp('window_to')->nullable();
            $table->integer('service_time_minutes')->default(5);
            $table->json('constraints')->nullable(); // Additional constraints
        });

        // Performance Monitoring
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->integer('response_time_ms');
            $table->integer('memory_usage_mb');
            $table->string('status_code');
            $table->json('metadata')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['org_id', 'endpoint', 'measured_at']);
        });

        // GDPR Requests
        Schema::create('gdpr_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('org_id')->nullable();
            $table->enum('type', ['export', 'erase', 'rectify', 'portability']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->text('description')->nullable();
            $table->string('result_url')->nullable(); // Download link for export
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        // Data Warehouse Tables (simplified)
        Schema::create('dw_f_orders', function (Blueprint $table) {
            $table->uuid('order_id');
            $table->uuid('org_id');
            $table->date('date');
            $table->string('module');
            $table->string('zone');
            $table->decimal('amount', 12, 2);
            $table->decimal('cost', 12, 2);
            $table->decimal('margin', 12, 2);
            $table->string('status');
            $table->timestamps();

            $table->index(['org_id', 'date', 'module']);
        });

        Schema::create('dw_d_date', function (Blueprint $table) {
            $table->date('d')->primary();
            $table->integer('y');
            $table->integer('m');
            $table->integer('dow'); // Day of week
            $table->boolean('is_holiday')->default(false);
            $table->string('season')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dw_d_date');
        Schema::dropIfExists('dw_f_orders');
        Schema::dropIfExists('gdpr_requests');
        Schema::dropIfExists('performance_metrics');

        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropColumn(['window_from', 'window_to', 'service_time_minutes', 'constraints']);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['profile', 'weather_conditions', 'turn_penalty', 'left_turn_penalty', 'prioritize_highways']);
        });

        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('search_indexes');

        // Remove org_id from existing tables
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('geo_zones', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_org_id']);
            $table->dropColumn('default_org_id');
        });

        Schema::dropIfExists('organization_users');
        Schema::dropIfExists('organization_settings');
        Schema::dropIfExists('organizations');
    }
};
