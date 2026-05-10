<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('service_categories', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            if (! Schema::hasColumn('service_categories', 'short_description')) {
                $table->string('short_description', 255)->nullable()->after('description');
            }

            if (! Schema::hasColumn('service_categories', 'icon')) {
                $table->string('icon', 128)->nullable()->after('short_description');
            }

            if (! Schema::hasColumn('service_categories', 'color')) {
                $table->string('color', 32)->nullable()->after('icon');
            }

            if (! Schema::hasColumn('service_categories', 'order_column')) {
                $table->unsignedSmallInteger('order_column')->default(0)->after('color');
            }

            if (! Schema::hasColumn('service_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('order_column');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('service_categories', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('service_categories', 'order_column')) {
                $table->dropColumn('order_column');
            }

            if (Schema::hasColumn('service_categories', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('service_categories', 'icon')) {
                $table->dropColumn('icon');
            }

            if (Schema::hasColumn('service_categories', 'short_description')) {
                $table->dropColumn('short_description');
            }

            if (Schema::hasColumn('service_categories', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
