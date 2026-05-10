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
        Schema::table('assistant_conversations', function (Blueprint $table) {
            $table->index('created_at', 'assistant_conversations_created_at_idx');
            $table->index('channel', 'assistant_conversations_channel_idx');
            $table->index('created_by', 'assistant_conversations_created_by_idx');
            $table->index(['subject_type', 'subject_id'], 'assistant_conversations_subject_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_conversations', function (Blueprint $table) {
            $table->dropIndex('assistant_conversations_created_at_idx');
            $table->dropIndex('assistant_conversations_channel_idx');
            $table->dropIndex('assistant_conversations_created_by_idx');
            $table->dropIndex('assistant_conversations_subject_idx');
        });
    }
};
