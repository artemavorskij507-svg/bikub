<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('service_categories', 'show_on_homepage')) {
                $table->boolean('show_on_homepage')->default(true)->after('is_active');
            }

            if (! Schema::hasColumn('service_categories', 'homepage_order')) {
                $table->unsignedSmallInteger('homepage_order')->default(0)->after('show_on_homepage');
            }

            if (! Schema::hasColumn('service_categories', 'icon_svg')) {
                $table->text('icon_svg')->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('service_categories', 'icon_svg')) {
                $table->dropColumn('icon_svg');
            }

            if (Schema::hasColumn('service_categories', 'homepage_order')) {
                $table->dropColumn('homepage_order');
            }

            if (Schema::hasColumn('service_categories', 'show_on_homepage')) {
                $table->dropColumn('show_on_homepage');
            }
        });
    }
};
