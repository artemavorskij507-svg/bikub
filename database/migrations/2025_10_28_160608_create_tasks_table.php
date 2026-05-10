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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->string('status')->default('queued'); // queued|assigned|enroute|done|cancelled
            $table->integer('priority')->default(1); // 1=low, 2=normal, 3=high, 4=urgent
            $table->json('checklist')->nullable(); // task checklist items
            $table->json('proofs')->nullable(); // photos, geo coordinates
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
