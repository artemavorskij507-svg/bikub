<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_webhook_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('idempotency_key')->index();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->enum('status', ['pending', 'ok', 'failed', 'abandoned'])->default('pending')->index();
            $table->json('payload');
            $table->string('signature_sent')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_webhook_logs');
    }
};
