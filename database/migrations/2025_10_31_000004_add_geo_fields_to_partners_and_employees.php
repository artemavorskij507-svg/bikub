<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'current_location')) {
                $table->json('current_location')->nullable();
            }
            if (! Schema::hasColumn('partners', 'geo_fence')) {
                $table->json('geo_fence')->nullable();
            }
            if (! Schema::hasColumn('partners', 'available_from')) {
                $table->time('available_from')->nullable();
            }
            if (! Schema::hasColumn('partners', 'available_until')) {
                $table->time('available_until')->nullable();
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'current_location')) {
                $table->json('current_location')->nullable();
            }
            if (! Schema::hasColumn('employees', 'available_from')) {
                $table->time('available_from')->nullable();
            }
            if (! Schema::hasColumn('employees', 'available_until')) {
                $table->time('available_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            foreach (['current_location', 'geo_fence', 'available_from', 'available_until'] as $col) {
                if (Schema::hasColumn('partners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('employees', function (Blueprint $table) {
            foreach (['current_location', 'available_from', 'available_until'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
