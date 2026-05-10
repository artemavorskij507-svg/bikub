<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ad_images')) {
            return;
        }

        // Для PostgreSQL используем прямой SQL
        if (Schema::hasColumn('ad_images', 'file_path') && ! Schema::hasColumn('ad_images', 'path')) {
            DB::statement('ALTER TABLE ad_images RENAME COLUMN file_path TO path');
        }

        if (Schema::hasColumn('ad_images', 'order') && ! Schema::hasColumn('ad_images', 'sort_order')) {
            DB::statement('ALTER TABLE ad_images RENAME COLUMN "order" TO sort_order');
        }

        // Удаляем лишние колонки
        Schema::table('ad_images', function (Blueprint $table) {
            if (Schema::hasColumn('ad_images', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('ad_images', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::hasColumn('ad_images', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('ad_images', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ad_images', function (Blueprint $table) {
            if (Schema::hasColumn('ad_images', 'path') && ! Schema::hasColumn('ad_images', 'file_path')) {
                DB::statement('ALTER TABLE ad_images RENAME COLUMN path TO file_path');
            }
            if (Schema::hasColumn('ad_images', 'sort_order') && ! Schema::hasColumn('ad_images', 'order')) {
                DB::statement('ALTER TABLE ad_images RENAME COLUMN sort_order TO "order"');
            }

            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->boolean('is_primary')->default(false);
        });
    }
};
