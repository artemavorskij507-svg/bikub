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

        Schema::create('ad_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classified_ad_id')->constrained('classified_ads')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['classified_ad_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_images');
    }
};
