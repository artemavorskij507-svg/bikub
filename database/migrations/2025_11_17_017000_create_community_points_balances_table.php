<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_points_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_profile_id')->unique()->constrained('social_helper_profiles')->cascadeOnDelete();
            // $table->unsignedBigInteger('helper_profile_id')->unique();
            $table->integer('balance_points')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->timestamps();

            $table->index('helper_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_points_balances');
    }
};
