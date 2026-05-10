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
        Schema::table('executor_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('executor_profiles', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executor_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('executor_profiles', 'last_active_at')) {
                $table->dropColumn('last_active_at');
            }
        });
    }
};
