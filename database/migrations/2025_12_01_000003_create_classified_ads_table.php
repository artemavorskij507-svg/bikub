<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classified_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('ad_categories')->onDelete('restrict');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->jsonb('price_details')->nullable();
            $table->integer('price_value')->nullable()->comment('Price in cents');
            $table->enum('status', ['draft', 'moderation', 'published', 'sold', 'expired'])->default('draft');
            $table->string('address')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('slug')->nullable()->index();
            // Простые координаты вместо PostGIS geometry
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classified_ads');
    }
};
