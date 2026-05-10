<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('routes_cache', function (Blueprint $table) {
            $table->id();
            $table->string('request_hash')->unique();
            $table->json('payload');
            $table->json('result');
            $table->timestamp('created_at')->useCurrent();

            $table->index('request_hash');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes_cache');
    }
};
