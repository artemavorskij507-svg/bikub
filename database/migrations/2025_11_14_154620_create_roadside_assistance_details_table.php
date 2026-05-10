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
        Schema::create('roadside_assistance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('subtype')->nullable(); // jump_start, flat_tire, fuel_delivery, tow_short, tow_long, inspection

            // Локация инцидента
            $table->string('incident_address')->nullable();
            $table->decimal('incident_lat', 10, 7)->nullable();
            $table->decimal('incident_lng', 10, 7)->nullable();

            // Автомобиль
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->string('vehicle_color')->nullable();

            // Партнёр (эвакуатор / СТО)
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();

            // Для осмотра перед покупкой
            $table->string('inspection_report_url')->nullable();

            $table->json('extra')->nullable();

            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['partner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadside_assistance_details');
    }
};
