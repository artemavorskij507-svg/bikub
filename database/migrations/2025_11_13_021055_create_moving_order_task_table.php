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
        Schema::create('moving_order_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moving_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('task_type')->nullable()->comment('assembly, disposal, etc.');
            $table->timestamps();

            $table->unique(['moving_order_id', 'task_id']);
            $table->index('moving_order_id');
            $table->index('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_order_task');
    }
};
