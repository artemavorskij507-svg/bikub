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
        Schema::table('geo_zones', function (Blueprint $table) {
            // Add JSON geometry column instead of PostGIS (PostGIS not available)
            $table->json('spatial_geometry')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_zones', function (Blueprint $table) {
            $table->dropSpatialIndex(['spatial_geometry']);
            $table->dropColumn('spatial_geometry');
        });
    }
};
