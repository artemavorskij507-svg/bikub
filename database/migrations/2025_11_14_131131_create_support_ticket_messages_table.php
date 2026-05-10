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
        $ticketIdType = Schema::hasColumn('support_tickets', 'id')
            ? Schema::getColumnType('support_tickets', 'id')
            : 'uuid';

        Schema::create('support_ticket_messages', function (Blueprint $table) use ($ticketIdType) {
            $table->id();

            // SupportTicket використовує UUID (string) для id
            if ($ticketIdType === 'uuid') {
                $table->uuid('ticket_id');
            } else {
                $table->unsignedBigInteger('ticket_id');
            }
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('sender_type')->nullable(); // 'worker', 'dispatcher', 'admin', 'system'
            $table->text('message');

            $table->timestamp('read_at')->nullable(); // коли прочитав адресат
            $table->json('metadata')->nullable(); // ip, user_agent, etc.

            $table->timestamps();

            // Зовнішній ключ для ticket_id (UUID або bigint)
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');

            $table->index(['ticket_id']);
            $table->index(['user_id']);
            $table->index(['sender_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
