<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('role_context')->nullable(); // courier / executor / eco_executor / roadside_assist / social_helper / dispatcher

            $table->string('subject');
            $table->text('message');

            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->string('priority')->default('normal'); // low, normal, high, urgent

            $table->string('channel')->default('worker_lk'); // worker_lk, phone, auto, system, etc.

            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
