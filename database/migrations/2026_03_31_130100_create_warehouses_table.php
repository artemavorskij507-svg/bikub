<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouses')) {
            return;
        }

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->string('warehouse_type', 32)->default('hub');
            $table->foreignId('address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('timezone', 64)->default('Europe/Oslo');
            $table->unsignedInteger('capacity_parcels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_type', 'is_active'], 'warehouses_type_active_idx');
            $table->index(['partner_id', 'is_active'], 'warehouses_partner_active_idx');
            $table->index('address_id', 'warehouses_address_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
