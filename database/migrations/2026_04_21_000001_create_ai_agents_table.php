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
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department');
            $table->enum('status', ['active', 'standby', 'offline'])->default('standby');
            $table->boolean('is_core')->default(false);
            $table->enum('permissions_level', [
                'read-only',
                'draft-only',
                'approval-required',
                'break-glass'
            ])->default('read-only');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
