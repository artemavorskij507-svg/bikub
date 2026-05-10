<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_templates')) {
            Schema::create('contract_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->longText('body')->nullable();
                $table->string('status')->default('draft');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('status')->default('draft');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contract_events')) {
            Schema::create('contract_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->string('event_type');
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_events');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
    }
};

