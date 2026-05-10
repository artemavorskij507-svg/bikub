<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_helper_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // TODO: consider dedicated enum type for level
            $table->string('level'); // SOCIAL_HELPER / COMMUNITY_PARTNER / BIKUBE_FRIEND
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->boolean('has_police_certificate')->default(false);
            $table->timestamp('police_certificate_verified_at')->nullable();
            $table->timestamp('first_aid_trained_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timeTz('available_from')->nullable();
            $table->timeTz('available_to')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_helper_profiles');
    }
};
