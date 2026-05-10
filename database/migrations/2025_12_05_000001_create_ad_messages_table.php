<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('classified_ads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['ad_id', 'sender_id', 'receiver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_messages');
    }
};
