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
        if (! Schema::hasTable('geo_zones')) {
            Schema::create('geo_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->enum('type', ['circle', 'polygon', 'bbox', 'multi'])->default('polygon');
                $table->json('geometry'); // GeoJSON feature
                $table->json('meta')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(100);
                $table->string('description')->nullable();
                $table->string('source_file')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'priority']);
                $table->index('slug');
            });
        } else {
            // Update existing table
            Schema::table('geo_zones', function (Blueprint $table) {
                if (! Schema::hasColumn('geo_zones', 'type')) {
                    $table->enum('type', ['circle', 'polygon', 'bbox', 'multi'])->default('polygon')->after('slug');
                }
                if (! Schema::hasColumn('geo_zones', 'geometry')) {
                    $table->json('geometry')->nullable()->after('type');
                }
                if (! Schema::hasColumn('geo_zones', 'meta')) {
                    $table->json('meta')->nullable()->after('geometry');
                }
                if (! Schema::hasColumn('geo_zones', 'priority')) {
                    $table->integer('priority')->default(100)->after('is_active');
                }
                if (! Schema::hasColumn('geo_zones', 'source_file')) {
                    $table->string('source_file')->nullable()->after('metadata');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop table if it exists with data
        Schema::table('geo_zones', function (Blueprint $table) {
            if (Schema::hasColumn('geo_zones', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('geo_zones', 'geometry')) {
                $table->dropColumn('geometry');
            }
            if (Schema::hasColumn('geo_zones', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('geo_zones', 'source_file')) {
                $table->dropColumn('source_file');
            }
        });
    }
};
