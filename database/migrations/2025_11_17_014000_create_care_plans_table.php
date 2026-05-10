<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->cascadeOnDelete();
            $table->foreignId('trusted_contact_id')->nullable()->constrained('trusted_contacts')->nullOnDelete();
            $table->foreignId('care_service_id')->constrained('care_services')->restrictOnDelete();
            $table->string('service_type_code')->nullable();
            $table->string('frequency'); // DAILY, WEEKLY, BIWEEKLY, MONTHLY, CUSTOM
            $table->smallInteger('day_of_week')->nullable(); // 0-6 для weekly
            $table->time('time_of_day')->nullable();
            $table->integer('duration_minutes');
            $table->string('preferred_helper_level')->nullable(); // SOCIAL_HELPER, COMMUNITY_PARTNER, BIKUBE_FRIEND
            $table->foreignId('preferred_helper_id')->nullable()->constrained('social_helper_profiles')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('status'); // ACTIVE, PAUSED, CANCELLED, COMPLETED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('client_profile_id');
            $table->index('care_service_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
