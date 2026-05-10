<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('price_override_cents')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_services');
    }
};
