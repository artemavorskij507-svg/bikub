<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('geo_zone_partner')) {
            Schema::create('geo_zone_partner', function (Blueprint $t) {
                $t->bigInteger('partner_id')->index();
                $t->bigInteger('geo_zone_id')->index();
                $t->primary(['partner_id', 'geo_zone_id']);
                $t->json('window')->nullable();
            });
        }

        if (! Schema::hasTable('partner_service_type')) {
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
        Schema::dropIfExists('geo_zone_partner');
        Schema::dropIfExists('partner_service_type');
    }
};
