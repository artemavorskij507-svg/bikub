<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique(); // winter_protocol, auto_assign, strict_payment_gate
                $table->boolean('enabled')->default(false);
                $table->json('settings')->nullable(); // дополнительные настройки флага
                $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('enabled_at')->nullable();
                $table->text('reason')->nullable(); // причина включения/выключения
                $table->timestamps();

                $table->index(['key', 'enabled']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
