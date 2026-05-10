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
        // Для PostgreSQL нужно использовать DB::statement для изменения колонки
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pricing_rules DROP CONSTRAINT IF EXISTS pricing_rules_service_type_id_foreign');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pricing_rules ALTER COLUMN service_type_id DROP NOT NULL');

        Schema::table('pricing_rules', function (Blueprint $table) {
            // Восстанавливаем foreign key constraint
            $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('cascade');

            // Добавляем service_type как строку для прямого указания типа доставки (grocery, bulky, food)
            if (! Schema::hasColumn('pricing_rules', 'service_type')) {
                $table->string('service_type')->nullable()->after('service_type_id');
            }

            // Добавляем geo_zone_id для привязки правила к геозоне
            if (! Schema::hasColumn('pricing_rules', 'geo_zone_id')) {
                $table->foreignId('geo_zone_id')->nullable()->after('service_type')
                    ->constrained('geo_zones')->onDelete('cascade');
            }

            // Переименовываем base_price в base_fee для delivery (но оставляем base_price для обратной совместимости)
            // Вместо переименования добавим base_fee как отдельное поле
            if (! Schema::hasColumn('pricing_rules', 'base_fee')) {
                $table->decimal('base_fee', 10, 2)->nullable()->after('base_price');
            }

            // Добавляем поля для delivery-специфичных тарифов
            if (! Schema::hasColumn('pricing_rules', 'per_km_fee')) {
                $table->decimal('per_km_fee', 10, 2)->nullable()->after('base_fee');
            }

            if (! Schema::hasColumn('pricing_rules', 'per_m3_fee')) {
                $table->decimal('per_m3_fee', 10, 2)->nullable()->after('per_km_fee');
            }

            if (! Schema::hasColumn('pricing_rules', 'per_kg_fee')) {
                $table->decimal('per_kg_fee', 10, 2)->nullable()->after('per_m3_fee');
            }

            if (! Schema::hasColumn('pricing_rules', 'urgency_multiplier')) {
                $table->decimal('urgency_multiplier', 5, 2)->default(1.0)->after('per_kg_fee');
            }

            if (! Schema::hasColumn('pricing_rules', 'night_multiplier')) {
                $table->decimal('night_multiplier', 5, 2)->default(1.0)->after('urgency_multiplier');
            }

            // Индекс для быстрого поиска правил по типу сервиса и геозоне
            if (! $this->hasIndex('pricing_rules', 'pricing_rules_service_type_zone_idx')) {
                $table->index(['service_type', 'geo_zone_id', 'is_active'], 'pricing_rules_service_type_zone_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            // Удаляем индекс если существует
            try {
                if ($this->hasIndex('pricing_rules', 'pricing_rules_service_type_zone_idx')) {
                    $table->dropIndex('pricing_rules_service_type_zone_idx');
                }
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }

            if (Schema::hasColumn('pricing_rules', 'night_multiplier')) {
                $table->dropColumn('night_multiplier');
            }
            if (Schema::hasColumn('pricing_rules', 'urgency_multiplier')) {
                $table->dropColumn('urgency_multiplier');
            }
            if (Schema::hasColumn('pricing_rules', 'per_kg_fee')) {
                $table->dropColumn('per_kg_fee');
            }
            if (Schema::hasColumn('pricing_rules', 'per_m3_fee')) {
                $table->dropColumn('per_m3_fee');
            }
            if (Schema::hasColumn('pricing_rules', 'per_km_fee')) {
                $table->dropColumn('per_km_fee');
            }
            if (Schema::hasColumn('pricing_rules', 'base_fee')) {
                $table->dropColumn('base_fee');
            }
            if (Schema::hasColumn('pricing_rules', 'geo_zone_id')) {
                $table->dropForeign(['geo_zone_id']);
                $table->dropColumn('geo_zone_id');
            }
            if (Schema::hasColumn('pricing_rules', 'service_type')) {
                $table->dropColumn('service_type');
            }

            // Восстанавливаем service_type_id как NOT NULL (если нужно)
            // Но лучше оставить nullable для обратной совместимости
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $doctrineSchemaManager->introspectTable($table);

            return $doctrineTable->hasIndex($indexName);
        } catch (\Exception $e) {
            return false;
        }
    }
};
