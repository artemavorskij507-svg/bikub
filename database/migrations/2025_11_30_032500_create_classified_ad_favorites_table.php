<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if classified_ads table exists first
        if (! Schema::hasTable('classified_ads')) {
            return;
        }

        Schema::create('classified_ad_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('classified_ad_id')->constrained('classified_ads')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'classified_ad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classified_ad_favorites');
    }
};
