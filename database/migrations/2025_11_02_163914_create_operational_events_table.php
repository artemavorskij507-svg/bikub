<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('operational_events')) {
            Schema::create('operational_events', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // sla_risk, slot_overbook, webhook_failed, stripe_error, incident_alert
                $table->string('severity')->default('info'); // info, warning, error, critical
                $table->string('title');
                $table->text('message')->nullable();
                $table->json('context')->nullable(); // дополнительные данные
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'severity']);
                $table->index(['is_resolved', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_events');
    }
};
