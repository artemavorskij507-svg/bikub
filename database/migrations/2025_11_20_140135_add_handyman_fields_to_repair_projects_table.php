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
        Schema::table('repair_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('repair_projects', 'base_price')) {
                $table->integer('base_price')->nullable()->after('budget_actual_minor')->comment('Base price in NOK');
            }

            if (! Schema::hasColumn('repair_projects', 'estimated_time')) {
                $table->string('estimated_time')->nullable()->after('base_price');
            }

            if (! Schema::hasColumn('repair_projects', 'region')) {
                $table->string('region')->nullable()->after('estimated_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_projects', function (Blueprint $table) {
            if (Schema::hasColumn('repair_projects', 'region')) {
                $table->dropColumn('region');
            }

            if (Schema::hasColumn('repair_projects', 'estimated_time')) {
                $table->dropColumn('estimated_time');
            }

            if (Schema::hasColumn('repair_projects', 'base_price')) {
                $table->dropColumn('base_price');
            }
        });
    }
};
