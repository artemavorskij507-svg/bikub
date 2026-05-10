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
        Schema::create('handyman_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handyman_service_id')->nullable()->constrained('handyman_services')->nullOnDelete();
            $table->boolean('is_custom_request')->default(false);
            $table->text('description')->nullable();
            $table->text('context_notes')->nullable();
            $table->boolean('needs_materials_purchase')->default(false);
            $table->text('materials_notes')->nullable();
            $table->unsignedInteger('expected_duration_minutes')->nullable();
            $table->string('address_line')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('city', 64)->nullable();
            $table->json('attachments')->nullable();
            $table->bigInteger('estimated_price_minor')->nullable();
            $table->bigInteger('final_price_minor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_order_details');
    }
};
