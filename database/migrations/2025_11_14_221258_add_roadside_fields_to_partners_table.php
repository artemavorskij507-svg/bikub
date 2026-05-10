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
        Schema::table('partners', function (Blueprint $table) {
            // Додаємо geo_zone_id (основна зона, окрім pivot)
            if (! Schema::hasColumn('partners', 'geo_zone_id')) {
                $table->foreignId('geo_zone_id')
                    ->nullable()
                    ->after('longitude')
                    ->constrained('geo_zones')
                    ->nullOnDelete();
            }

            // Додаємо capabilities (JSON масив можливостей)
            if (! Schema::hasColumn('partners', 'capabilities')) {
                $table->json('capabilities')->nullable()->after('metadata');
            }

            // Додаємо priority (пріоритет для автоназначення)
            if (! Schema::hasColumn('partners', 'priority')) {
                $table->unsignedInteger('priority')->default(100)->after('is_active');
            }

            // Додаємо service_area (для майбутнього)
            if (! Schema::hasColumn('partners', 'service_area')) {
                $table->json('service_area')->nullable()->after('capabilities');
            }
        });

        // Додаємо індекс для type (якщо не існує)
        $connection = Schema::getConnection();
        $indexes = $connection->select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'partners' AND indexdef LIKE '%type%'");
        if (empty($indexes)) {
            Schema::table('partners', function (Blueprint $table) {
                $table->index('type', 'partners_type_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'geo_zone_id')) {
                $table->dropForeign(['geo_zone_id']);
                $table->dropColumn('geo_zone_id');
            }

            if (Schema::hasColumn('partners', 'capabilities')) {
                $table->dropColumn('capabilities');
            }

            if (Schema::hasColumn('partners', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('partners', 'service_area')) {
                $table->dropColumn('service_area');
            }
        });
    }
};
