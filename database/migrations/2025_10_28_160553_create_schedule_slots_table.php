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
        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // morning/day/evening
            $table->string('name');
            $table->time('from');
            $table->time('to');
            $table->json('dow')->nullable(); // [1,2,3,4,5] - days of week
            $table->boolean('is_active')->default(true);
            $table->integer('max_orders')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('schedule_slot_id')->nullable()->constrained('schedule_slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['schedule_slot_id']);
            $table->dropColumn('schedule_slot_id');
        });

        Schema::dropIfExists('schedule_slots');
    }
};
