<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classified_ads')) {
            return;
        }

        Schema::create('classified_ad_bumps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classified_ad_id')->constrained('classified_ads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('bumped_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classified_ad_bumps');
    }
};
