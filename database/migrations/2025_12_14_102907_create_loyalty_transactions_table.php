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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['earn', 'redeem', 'manual_add', 'manual_remove', 'expire', 'admin_adjustment']);
            $table->integer('points_amount');
            $table->text('description')->nullable();
            $table->string('source_type')->nullable()->comment('Eloquent model class');
            $table->unsignedBigInteger('source_id')->nullable()->comment('ID of source model');
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
