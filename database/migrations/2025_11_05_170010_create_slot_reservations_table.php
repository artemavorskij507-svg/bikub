<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('schedule_slots')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 16)->default('hold')->index();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('confirmed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'slot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_reservations');
    }
};
