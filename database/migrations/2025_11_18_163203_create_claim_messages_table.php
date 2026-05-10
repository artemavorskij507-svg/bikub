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
        Schema::create('claim_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('claim_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // роль отправителя на момент сообщения (customer/support/dispatcher/pm/executor)
            $table->string('sender_role', 32)->nullable();

            $table->text('body');

            $table->json('meta')->nullable(); // файлы, вложения, системные пометки

            $table->timestamps();

            $table->index(['claim_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_messages');
    }
};
