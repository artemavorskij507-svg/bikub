<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            if (! Schema::hasColumn('classified_ads', 'moderation_reason')) {
                // Без ->after('status'), чтобы не ломать PostgreSQL
                $table->text('moderation_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            if (Schema::hasColumn('classified_ads', 'moderation_reason')) {
                $table->dropColumn('moderation_reason');
            }
        });
    }
};
