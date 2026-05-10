<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Виправляємо типи в geo_zone_partner
        // geo_zones.id - bigint, тому geo_zone_id має бути bigint
        // partners.id - bigint, тому partner_id має бути bigint

        if (Schema::hasTable('geo_zone_partner')) {
            // Просто перестворюємо таблицю з правильними типами
            // Оскільки таблиця порожня, це безпечно
            Schema::dropIfExists('geo_zone_partner');

            Schema::create('geo_zone_partner', function (Blueprint $t) {
                $t->bigInteger('partner_id')->index();
                $t->bigInteger('geo_zone_id')->index();
                $t->primary(['partner_id', 'geo_zone_id']);
                $t->json('window')->nullable();
            });
        }

        // Виправляємо типи в partner_service_type
        // service_types.id - bigint, тому service_type_id має бути bigint
        // partners.id - bigint, тому partner_id має бути bigint

        if (Schema::hasTable('partner_service_type')) {
            // Просто перестворюємо таблицю з правильними типами
            // Оскільки таблиця порожня, це безпечно
            Schema::dropIfExists('partner_service_type');

            Schema::create('partner_service_type', function (Blueprint $t) {
                $t->bigInteger('partner_id')->index();
                $t->bigInteger('service_type_id')->index();
                $t->primary(['partner_id', 'service_type_id']);
                $t->integer('base_fee_cents')->default(0);
                $t->integer('per_km_cents')->default(0);
                $t->smallInteger('sla_minutes')->default(60);
                $t->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        // Відкат небезпечний, тому просто логуємо
        \Log::info('Rollback of fix_geo_zone_partner_types migration is not recommended');
    }
};
