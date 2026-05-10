<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classified_ad_favorites')) {
            return;
        }

        if (! Schema::hasTable('classified_ads') || ! Schema::hasTable('users')) {
            return;
        }

        Schema::create('classified_ad_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classified_ad_id')->constrained('classified_ads')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'classified_ad_id'], 'classified_ad_favorites_user_ad_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classified_ad_favorites');
    }
};

