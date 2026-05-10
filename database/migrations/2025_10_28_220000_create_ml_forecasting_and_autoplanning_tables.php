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
        // ML Feature Store
        if (! Schema::hasTable('ml_feature_store')) {
            Schema::create('ml_feature_store', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id');
                $table->string('slot_code');
                $table->date('date');
                $table->integer('hour')->nullable();
                $table->json('features'); // orders, weather, holidays, promotions, etc.
                $table->timestamps();

                $table->index(['zone_id', 'slot_code', 'date', 'hour']);
            });
        }

        // Forecast Capacity
        if (! Schema::hasTable('forecast_capacity')) {
            Schema::create('forecast_capacity', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id');
                $table->string('slot_code');
                $table->date('date');
                $table->decimal('demand_mean', 8, 2);
                $table->decimal('demand_p90', 8, 2);
                $table->decimal('demand_p95', 8, 2);
                $table->decimal('confidence_score', 3, 2);
                $table->string('model_version');
                $table->timestamps();

                $table->unique(['zone_id', 'slot_code', 'date']);
                $table->index(['date', 'zone_id']);
            });
        }

        // ML Models Registry
        if (! Schema::hasTable('ml_models')) {
            Schema::create('ml_models', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('version');
                $table->string('type'); // demand_forecast, eta_prediction, recommendation
                $table->json('config');
                $table->json('metrics');
                $table->string('status'); // training, active, deprecated
                $table->timestamp('trained_at')->nullable();
                $table->timestamp('deployed_at')->nullable();
                $table->timestamps();

                $table->unique(['name', 'version']);
                $table->index(['type', 'status']);
            });
        }

        // ML Predictions Cache
        if (! Schema::hasTable('ml_predictions_cache')) {
            Schema::create('ml_predictions_cache', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('prediction_type');
                $table->string('cache_key');
                $table->json('input_features');
                $table->json('prediction_result');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['prediction_type', 'cache_key']);
                $table->index(['expires_at']);
            });
        }

        // Auto Planning Jobs
        if (! Schema::hasTable('auto_planning_jobs')) {
            Schema::create('auto_planning_jobs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type'); // capacity_planning, shift_scheduling, route_optimization
                $table->json('parameters');
                $table->string('status'); // pending, running, completed, failed
                $table->json('result')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('scheduled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index(['scheduled_at']);
            });
        }

        // Planning Suggestions
        if (! Schema::hasTable('planning_suggestions')) {
            Schema::create('planning_suggestions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type'); // capacity_increase, shift_addition, route_optimization
                $table->uuid('zone_id');
                $table->string('slot_code')->nullable();
                $table->date('date');
                $table->json('suggestion_data');
                $table->decimal('confidence_score', 3, 2);
                $table->string('status'); // pending, accepted, rejected
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index(['zone_id', 'date']);
            });
        }

        // Capacity Adjustments
        if (! Schema::hasTable('capacity_adjustments')) {
            Schema::create('capacity_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id');
                $table->string('slot_code');
                $table->date('date');
                $table->integer('original_capacity');
                $table->integer('adjusted_capacity');
                $table->string('adjustment_reason');
                $table->uuid('adjusted_by');
                $table->timestamps();

                $table->index(['zone_id', 'slot_code', 'date']);
            });
        }

        // ML Performance Metrics
        if (! Schema::hasTable('ml_performance_metrics')) {
            Schema::create('ml_performance_metrics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('model_id');
                $table->string('metric_name'); // MAE, MAPE, RMSE, accuracy
                $table->decimal('metric_value', 10, 4);
                $table->json('metadata');
                $table->timestamp('evaluated_at');
                $table->timestamps();

                $table->index(['model_id', 'metric_name']);
                $table->index(['evaluated_at']);
            });
        }

        // Weather Data for ML
        if (! Schema::hasTable('weather_data')) {
            Schema::create('weather_data', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id');
                $table->timestamp('timestamp');
                $table->decimal('temperature', 5, 2);
                $table->decimal('humidity', 5, 2);
                $table->decimal('wind_speed', 5, 2);
                $table->decimal('precipitation', 5, 2);
                $table->string('weather_condition');
                $table->json('raw_data');
                $table->timestamps();

                $table->index(['zone_id', 'timestamp']);
            });
        }

        // Holiday Calendar
        if (! Schema::hasTable('holiday_calendar')) {
            Schema::create('holiday_calendar', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('country_code', 3);
                $table->date('date');
                $table->string('name');
                $table->string('type'); // national, regional, religious
                $table->boolean('affects_demand');
                $table->timestamps();

                $table->index(['country_code', 'date']);
                $table->index(['affects_demand']);
            });
        }

        // Demand Patterns
        if (! Schema::hasTable('demand_patterns')) {
            Schema::create('demand_patterns', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('zone_id');
                $table->string('pattern_type'); // hourly, daily, weekly, seasonal
                $table->json('pattern_data');
                $table->decimal('confidence', 3, 2);
                $table->timestamp('analyzed_at');
                $table->timestamps();

                $table->index(['zone_id', 'pattern_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demand_patterns');
        Schema::dropIfExists('holiday_calendar');
        Schema::dropIfExists('weather_data');
        Schema::dropIfExists('ml_performance_metrics');
        Schema::dropIfExists('capacity_adjustments');
        Schema::dropIfExists('planning_suggestions');
        Schema::dropIfExists('auto_planning_jobs');
        Schema::dropIfExists('ml_predictions_cache');
        Schema::dropIfExists('ml_models');
        Schema::dropIfExists('forecast_capacity');
        Schema::dropIfExists('ml_feature_store');
    }
};
