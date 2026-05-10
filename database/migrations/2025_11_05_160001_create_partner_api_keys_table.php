<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('prefix', 16)->index();
            $table->string('key_hash');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('rate_limit_per_min')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_api_keys');
    }
};
