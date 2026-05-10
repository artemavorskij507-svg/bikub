<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_schedule_slot')) {
            Schema::create('order_schedule_slot', function (Blueprint $table) {
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('slot_id')->constrained('schedule_slots')->cascadeOnDelete();
                $table->enum('reservation_status', ['hold', 'confirmed'])->default('hold')->index();
                $table->timestampTz('expires_at')->nullable();
                $table->primary(['order_id', 'slot_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_schedule_slot');
    }
};
