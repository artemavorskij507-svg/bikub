<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_estimate_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('service_type')->index();
            $table->string('zone')->nullable()->index();
            $table->string('currency', 3)->default('NOK');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('request_hash')->index();
            $table->json('payload');
            $table->json('result');
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_estimate_logs');
    }
};
