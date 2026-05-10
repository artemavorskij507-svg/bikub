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
        Schema::create('social_care_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_care_order_created')->default(true);
            $table->boolean('notify_care_plan_created')->default(true);
            $table->boolean('notify_visit_status_changes')->default(true);
            $table->boolean('notify_visit_reports')->default(true);
            $table->boolean('notify_emergency')->default(true);
            $table->boolean('notify_reschedule_requests')->default(true);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_care_notification_settings');
    }
};
