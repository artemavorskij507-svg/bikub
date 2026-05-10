<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('executor_breaks')) {
            return;
        }

        Schema::create('executor_breaks', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id', 64)->nullable()->index();
            $table->string('tenant_id', 64)->nullable()->index();
            $table->unsignedBigInteger('executor_id')->index();
            $table->date('shift_date')->nullable()->index();
            $table->timestamp('break_start_at')->index();
            $table->timestamp('break_end_at')->index();
            $table->string('type', 50)->default('break')->index();
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executor_breaks');
    }
};
