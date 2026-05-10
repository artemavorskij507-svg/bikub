<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            $table->timestamp('bumped_at')->nullable()->index();
            $table->timestamp('highlight_expires_at')->nullable()->index();
            $table->timestamp('top_expires_at')->nullable()->index();
            $table->timestamp('vip_expires_at')->nullable()->index();
        });

        Schema::table('ad_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('ad_payments', 'meta')) {
                // jsonb без after() чтобы работать в PostgreSQL
                $table->jsonb('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            $table->dropColumn([
                'bumped_at',
                'highlight_expires_at',
                'top_expires_at',
                'vip_expires_at',
            ]);
        });

        Schema::table('ad_payments', function (Blueprint $table) {
            if (Schema::hasColumn('ad_payments', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
