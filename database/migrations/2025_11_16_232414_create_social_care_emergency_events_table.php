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
        Schema::create('social_care_emergency_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('helper_profile_id')->nullable()->constrained('social_helper_profiles')->nullOnDelete();
            $table->foreignId('client_profile_id')->nullable()->constrained('client_profiles')->nullOnDelete();
            $table->foreignId('triggered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source')->default('HELPER_APP'); // HELPER_APP, CLIENT_APP, COORDINATOR
            $table->string('level')->default('WARNING'); // INFO, WARNING, CRITICAL
            $table->text('message')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('helper_profile_id');
            $table->index('client_profile_id');
            $table->index('level');
            $table->index('handled_at');
            $table->index(['level', 'handled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_care_emergency_events');
    }
};
