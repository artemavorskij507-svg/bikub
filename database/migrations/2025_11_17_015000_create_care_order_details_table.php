<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->restrictOnDelete();
            $table->foreignId('trusted_contact_id')->nullable()->constrained('trusted_contacts')->nullOnDelete();
            $table->foreignId('care_service_id')->constrained('care_services')->restrictOnDelete();
            $table->foreignId('care_plan_id')->nullable()->constrained('care_plans')->nullOnDelete();
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at')->nullable();
            $table->foreignId('assigned_helper_id')->nullable()->constrained('social_helper_profiles')->nullOnDelete();
            $table->string('requested_helper_level')->nullable();
            $table->decimal('price_nok', 10, 2)->nullable();
            $table->text('notes_for_helper')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('client_profile_id');
            $table->index('care_plan_id');
            $table->index('assigned_helper_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_order_details');
    }
};
