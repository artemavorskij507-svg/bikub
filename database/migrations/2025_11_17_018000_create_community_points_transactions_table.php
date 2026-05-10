<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_profile_id')->constrained('social_helper_profiles')->cascadeOnDelete();
            // $table->unsignedBigInteger('helper_profile_id');
            $table->integer('delta_points'); // может быть + или -
            $table->string('reason_code'); // VISIT_COMPLETED, BONUS, ADJUSTMENT, REDEMPTION
            $table->jsonb('meta')->nullable(); // например, care_order_id, описание
            $table->timestamp('created_at');

            $table->index('helper_profile_id');
            $table->index('reason_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_points_transactions');
    }
};
