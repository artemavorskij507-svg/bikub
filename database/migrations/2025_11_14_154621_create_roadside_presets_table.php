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
        Schema::create('roadside_presets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // jump_start, flat_tire, fuel_delivery, tow_short, tow_long, inspection
            $table->string('label'); // Название для клиентов
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('service_type'); // roadside_assistance, vehicle_inspection, vehicle_transport
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadside_presets');
    }
};
