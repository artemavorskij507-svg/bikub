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
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role')->default('user'); // user | assistant | system
            $table->text('content');
            $table->json('meta')->nullable();
            $table->boolean('from_ai')->default(false);
            $table->timestamps();

            // Speeds up conversation timelines and admin listing filters/sorts.
            $table->index(['assistant_conversation_id', 'created_at'], 'assistant_messages_conv_created_idx');
            $table->index('user_id', 'assistant_messages_user_idx');
            $table->index('role', 'assistant_messages_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
