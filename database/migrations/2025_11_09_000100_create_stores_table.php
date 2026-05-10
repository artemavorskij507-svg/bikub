<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('order_column')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
