<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pricing_rule_logistics_scopes')) {
            return;
        }

        Schema::create('pricing_rule_logistics_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('geo_zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('scope_name', 80)->nullable();
            $table->decimal('distance_from_km', 10, 3)->nullable();
            $table->decimal('distance_to_km', 10, 3)->nullable();
            $table->decimal('weight_from_kg', 10, 3)->nullable();
            $table->decimal('weight_to_kg', 10, 3)->nullable();
            $table->decimal('multiplier', 8, 4)->default(1.0000);
            $table->decimal('fixed_surcharge', 12, 2)->default(0);
            $table->char('currency', 3)->default('NOK');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['pricing_rule_id', 'is_active', 'priority'], 'prls_rule_active_priority_idx');
            $table->index(['service_type_id', 'is_active'], 'prls_service_active_idx');
            $table->index(['geo_zone_id', 'is_active'], 'prls_zone_active_idx');
            $table->index(['warehouse_id', 'is_active'], 'prls_wh_active_idx');
            $table->index(['valid_from', 'valid_until'], 'prls_validity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rule_logistics_scopes');
    }
};
