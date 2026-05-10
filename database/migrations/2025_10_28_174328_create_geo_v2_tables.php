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
        // User Devices for PWA (skip if exists)
        if (! Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('device_id', 128);
                $table->text('fcm_token')->nullable();
                $table->json('meta')->nullable(); // device info, capabilities
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'device_id']);
                $table->index(['user_id', 'is_active']);
            });
        }

        // Addresses with spatial data (skip if exists)
        if (! Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->string('street_address');
                $table->string('city');
                $table->string('postal_code');
                $table->string('country')->default('NO');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->text('formatted_address');
                $table->json('meta')->nullable(); // additional address data
                $table->timestamps();

                $table->index(['latitude', 'longitude']);
                $table->index(['postal_code', 'city']);
            });
        }

        // Geo Zones already has the needed columns, just add indexes if they don't exist
        try {
            Schema::table('geo_zones', function (Blueprint $table) {
                $table->index(['type', 'is_active']);
                $table->index(['center_latitude', 'center_longitude']);
            });
        } catch (\Exception $e) {
            // Indexes might already exist, ignore error
        }

        // Route Matrix Cache
        Schema::create('route_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('from_address');
            $table->string('to_address');
            $table->decimal('from_lat', 10, 8);
            $table->decimal('from_lng', 11, 8);
            $table->decimal('to_lat', 10, 8);
            $table->decimal('to_lng', 11, 8);
            $table->integer('distance_meters');
            $table->integer('duration_seconds');
            $table->string('mode')->default('driving'); // driving/walking/cycling
            $table->json('route_data')->nullable(); // full route details
            $table->timestamp('cached_at');
            $table->timestamps();

            $table->unique(['from_lat', 'from_lng', 'to_lat', 'to_lng', 'mode']);
            $table->index(['cached_at']);
        });

        // Weather Data
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();
            $table->string('location_code'); // city/postal code
            $table->date('date');
            $table->time('time');
            $table->decimal('temperature', 5, 2); // Celsius
            $table->decimal('humidity', 5, 2); // percentage
            $table->decimal('wind_speed', 5, 2); // km/h
            $table->decimal('precipitation', 5, 2); // mm
            $table->string('condition'); // clear/rain/snow/fog
            $table->json('raw_data')->nullable(); // full API response
            $table->timestamps();

            $table->unique(['location_code', 'date', 'time']);
            $table->index(['location_code', 'date']);
        });

        // SLA Policies with weather coefficients
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('base_minutes'); // base SLA in minutes
            $table->decimal('night_coef', 4, 2)->default(1.0); // night multiplier
            $table->decimal('snow_coef', 4, 2)->default(1.0); // snow multiplier
            $table->decimal('overload_coef', 4, 2)->default(1.0); // slot overload multiplier
            $table->json('conditions')->nullable(); // additional conditions
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Route Optimization Jobs
        Schema::create('route_optimization_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('route_id');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
            $table->string('status')->default('queued'); // queued/running/completed/failed
            $table->json('input_data'); // orders, constraints
            $table->json('result_data')->nullable(); // optimized route
            $table->integer('optimization_time_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['route_id', 'status']);
        });

        // Update existing tables with geo fields
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->foreignId('geo_zone_id')->nullable()->constrained('geo_zones')->onDelete('set null');
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->onDelete('set null');
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breach_risk')->default(false);
            $table->json('weather_conditions')->nullable();
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->integer('total_distance_meters')->nullable();
            $table->integer('total_duration_seconds')->nullable();
            $table->decimal('optimization_score', 5, 2)->nullable();
            $table->json('weather_impact')->nullable();
        });

        Schema::table('route_stops', function (Blueprint $table) {
            $table->integer('distance_from_previous')->nullable(); // meters
            $table->integer('duration_from_previous')->nullable(); // seconds
            $table->timestamp('actual_arrival')->nullable();
            $table->timestamp('actual_departure')->nullable();
            $table->json('weather_at_arrival')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropColumn([
                'distance_from_previous',
                'duration_from_previous',
                'actual_arrival',
                'actual_departure',
                'weather_at_arrival',
            ]);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'total_distance_meters',
                'total_duration_seconds',
                'optimization_score',
                'weather_impact',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropForeign(['geo_zone_id']);
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn([
                'address_id',
                'geo_zone_id',
                'sla_policy_id',
                'sla_deadline',
                'sla_breach_risk',
                'weather_conditions',
            ]);
        });

        Schema::dropIfExists('route_optimization_jobs');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('weather_data');
        Schema::dropIfExists('route_matrices');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('user_devices');

        // Revert geo_zones changes
        Schema::table('geo_zones', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['center_lat', 'center_lng']);
            $table->dropColumn(['type', 'center_lat', 'center_lng', 'radius_meters', 'polygon_coordinates', 'meta']);
        });
    }
};
