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
        Schema::create('geo_zone_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_zone_id')->constrained('geo_zones')->onDelete('cascade');
            $table->string('key');
            $table->json('value');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['geo_zone_id', 'active']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_zone_rules');
    }
};
