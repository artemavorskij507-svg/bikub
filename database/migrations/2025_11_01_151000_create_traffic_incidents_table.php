<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('severity')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->json('geometry')->nullable();
            $table->json('meta')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->unique(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_incidents');
    }
};
