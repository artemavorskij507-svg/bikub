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
        Schema::create('care_order_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_new_start_at');
            $table->timestamp('requested_new_end_at')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('PENDING')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'requested_new_start_at'], 'care_change_order_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_order_change_requests');
    }
};
