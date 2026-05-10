<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            return;
        }

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->string('city', 128);
            $table->string('region', 128)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->default('NO');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default'], 'customer_addresses_user_default_idx');
            $table->index(['country_code', 'city'], 'customer_addresses_country_city_idx');
            $table->index(['country_code', 'postal_code'], 'customer_addresses_country_postal_idx');
            $table->index(['latitude', 'longitude'], 'customer_addresses_geo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
