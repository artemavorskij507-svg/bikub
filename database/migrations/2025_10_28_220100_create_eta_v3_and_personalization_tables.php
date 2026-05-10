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
        // ETA v3 ML Features
        if (! Schema::hasTable('eta_features')) {
            Schema::create('eta_features', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('route_id');
                $table->decimal('distance_km', 8, 3);
                $table->decimal('elevation_gain', 8, 2);
                $table->decimal('road_quality_score', 3, 2);
                $table->integer('traffic_level');
                $table->integer('hour_of_day');
                $table->integer('day_of_week');
                $table->decimal('temperature', 5, 2);
                $table->decimal('wind_speed', 5, 2);
                $table->decimal('precipitation', 5, 2);
                $table->decimal('courier_avg_speed', 5, 2);
                $table->json('road_segments');
                $table->timestamps();

                $table->index(['route_id']);
                $table->index(['hour_of_day', 'day_of_week']);
            });
        }

        // ETA Predictions
        if (! Schema::hasTable('eta_predictions')) {
            Schema::create('eta_predictions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('route_id');
                $table->uuid('model_id');
                $table->integer('predicted_eta_minutes');
                $table->decimal('confidence_score', 3, 2);
                $table->json('feature_importance');
                $table->timestamp('predicted_at');
                $table->timestamps();

                $table->index(['route_id']);
                $table->index(['model_id']);
            });
        }

        // Route Optimization Results
        if (! Schema::hasTable('route_optimizations')) {
            Schema::create('route_optimizations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_batch_id');
                $table->json('waypoints');
                $table->json('optimized_route');
                $table->integer('total_distance_km');
                $table->integer('total_time_minutes');
                $table->decimal('optimization_score', 5, 2);
                $table->string('algorithm_used');
                $table->timestamps();

                $table->index(['order_batch_id']);
            });
        }

        // Personalization Recommendations
        if (! Schema::hasTable('recommendation_models')) {
            Schema::create('recommendation_models', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type'); // item_item_cf, collaborative_filtering, content_based
                $table->string('category'); // market, food, services
                $table->json('config');
                $table->json('metrics');
                $table->string('status');
                $table->timestamp('trained_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'category', 'status']);
            });
        }

        // User Recommendations Cache
        if (! Schema::hasTable('user_recommendations')) {
            Schema::create('user_recommendations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('category');
                $table->json('recommendations');
                $table->decimal('confidence_score', 3, 2);
                $table->timestamp('generated_at');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['user_id', 'category']);
                $table->index(['expires_at']);
            });
        }

        // Recommendation Interactions
        if (! Schema::hasTable('recommendation_interactions')) {
            Schema::create('recommendation_interactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('recommendation_id');
                $table->string('interaction_type'); // view, click, add_to_cart, purchase
                $table->uuid('item_id');
                $table->string('item_type');
                $table->json('context');
                $table->timestamps();

                $table->index(['user_id', 'interaction_type']);
                $table->index(['recommendation_id']);
            });
        }

        // Smart Bundles
        if (! Schema::hasTable('smart_bundles')) {
            Schema::create('smart_bundles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('category');
                $table->json('items');
                $table->decimal('base_price', 10, 2);
                $table->decimal('bundle_discount', 5, 2);
                $table->json('rules');
                $table->boolean('is_active');
                $table->timestamps();

                $table->index(['category', 'is_active']);
            });
        }

        // Bundle Recommendations
        if (! Schema::hasTable('bundle_recommendations')) {
            Schema::create('bundle_recommendations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('bundle_id');
                $table->decimal('recommendation_score', 5, 2);
                $table->string('recommendation_reason');
                $table->timestamp('generated_at');
                $table->timestamps();

                $table->index(['user_id']);
                $table->index(['bundle_id']);
            });
        }

        // User Behavior Patterns
        if (! Schema::hasTable('user_behavior_patterns')) {
            Schema::create('user_behavior_patterns', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('pattern_type'); // browsing, purchasing, time_preference
                $table->json('pattern_data');
                $table->decimal('confidence', 3, 2);
                $table->timestamp('analyzed_at');
                $table->timestamps();

                $table->index(['user_id', 'pattern_type']);
            });
        }

        // A/B Testing for Recommendations
        if (! Schema::hasTable('recommendation_experiments')) {
            Schema::create('recommendation_experiments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type'); // algorithm_comparison, ui_variant
                $table->json('variants');
                $table->json('traffic_allocation');
                $table->string('status');
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['status']);
            });
        }

        // Experiment Assignments
        if (! Schema::hasTable('experiment_assignments')) {
            Schema::create('experiment_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('experiment_id');
                $table->uuid('user_id');
                $table->string('variant');
                $table->timestamp('assigned_at');
                $table->timestamps();

                $table->unique(['experiment_id', 'user_id']);
                $table->index(['experiment_id', 'variant']);
            });
        }

        // Add CO2 tracking to existing tables
        if (! Schema::hasColumn('routes', 'co2_grams')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->integer('co2_grams')->default(0)->after('distance_km');
            });
        }

        if (! Schema::hasColumn('orders', 'co2_grams')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('co2_grams')->default(0)->after('total_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('recommendation_experiments');
        Schema::dropIfExists('user_behavior_patterns');
        Schema::dropIfExists('bundle_recommendations');
        Schema::dropIfExists('smart_bundles');
        Schema::dropIfExists('recommendation_interactions');
        Schema::dropIfExists('user_recommendations');
        Schema::dropIfExists('recommendation_models');
        Schema::dropIfExists('route_optimizations');
        Schema::dropIfExists('eta_predictions');
        Schema::dropIfExists('eta_features');

        if (Schema::hasColumn('routes', 'co2_grams')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->dropColumn('co2_grams');
            });
        }

        if (Schema::hasColumn('orders', 'co2_grams')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('co2_grams');
            });
        }
    }
};
