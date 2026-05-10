<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Владелец
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            // Контакты и инфо
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->jsonb('working_hours')->nullable(); // { "mon": "9-18", ... }
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('classified_ads', function (Blueprint $table) {
            $table->foreignId('shop_id')
                ->nullable()
                ->after('user_id')
                ->constrained('shops')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classified_ads', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });

        Schema::dropIfExists('shops');
    }
};
