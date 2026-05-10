<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            if (! Schema::hasColumn('classified_ads', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0)->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
