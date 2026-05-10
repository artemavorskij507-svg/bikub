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
            if (! Schema::hasColumn('repair_projects', 'overall_progress_percent')) {
                $table->unsignedTinyInteger('overall_progress_percent')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_projects', function (Blueprint $table) {
            if (Schema::hasColumn('repair_projects', 'overall_progress_percent')) {
                $table->dropColumn('overall_progress_percent');
            }
        });
    }
};
