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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider')->default('stripe'); // stripe, vipps
            $table->string('provider_ref')->nullable(); // External payment ID
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NOK');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // card, vipps, etc.
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['provider', 'provider_ref']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
