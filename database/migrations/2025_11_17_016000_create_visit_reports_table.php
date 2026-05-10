<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_order_details_id')->constrained('care_order_details')->cascadeOnDelete();
            $table->foreignId('helper_profile_id')->constrained('social_helper_profiles')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->string('status'); // COMPLETED, PARTIALLY_COMPLETED, NOT_COMPLETED
            $table->text('summary');
            $table->string('client_mood')->nullable(); // HAPPY, NEUTRAL, CONCERNED
            $table->text('issues_noted')->nullable();
            $table->boolean('followup_recommended')->default(false);
            $table->text('followup_notes')->nullable();
            $table->timestamps();

            $table->index('care_order_details_id');
            $table->index('helper_profile_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};
