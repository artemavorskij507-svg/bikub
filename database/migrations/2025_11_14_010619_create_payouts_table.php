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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NOK');
            $table->enum('status', ['pending', 'processing', 'paid', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->string('method')->nullable(); // vipps, bank, cash, etc.
            $table->text('note')->nullable(); // Примечание от пользователя
            $table->text('admin_note')->nullable(); // Примечание от администратора
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
